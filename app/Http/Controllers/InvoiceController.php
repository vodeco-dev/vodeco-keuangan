<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\PublicConfirmPaymentRequest;
use App\Http\Requests\PublicStoreInvoiceRequest;
use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\Debt;
use App\Models\CustomerService;
use App\Models\InvoicePortalPassphrase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use App\Models\User;
use App\Services\CategoryService;
use App\Services\InvoicePdfService;
use App\Services\InvoiceSettlementService;
use App\Services\InvoiceViewService;
use App\Services\PassThroughInvoiceCreator;
use App\Services\PassThroughPackageManager;
use App\Services\VodecoWebsiteService;
use App\Support\PassThroughPackage;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class InvoiceController extends Controller
{
    public function __construct(
        private PassThroughInvoiceCreator $passThroughInvoiceCreator,
        private PassThroughPackageManager $passThroughPackageManager,
        private InvoicePdfService $invoicePdfService,
        private VodecoWebsiteService $vodecoWebsiteService,
        private InvoiceViewService $invoiceViewService
    )
    {
        $this->authorizeResource(Invoice::class, 'invoice');
    }
    public function index(Request $request): View
    {
        $user = auth()->user();
        $verifiedRoles = collect(session('verified_access_roles', []));

        $tabPermissions = [
            'needs-confirmation' => [
                'label' => 'Perlu Konfirmasi',
                'role' => null,
                'allowed' => Gate::allows('viewNeedsConfirmationTab', Invoice::class),
            ],
            'settlement' => [
                'label' => 'Pelunasan',
                'role' => null,
                'allowed' => Gate::allows('viewSettlementTab', Invoice::class),
            ],
            'history' => [
                'label' => 'Histori Invoices',
                'role' => null,
                'allowed' => Gate::allows('viewHistoryTab', Invoice::class),
            ],
        ];

        $tabStates = [];
        foreach ($tabPermissions as $key => $config) {
            $role = $config['role'];
            $allowed = $config['allowed'];
            $unlocked = $allowed;
            $requiresCode = false;

            if ($role instanceof Role) {
                $unlocked = $allowed && (
                    $user->role === Role::ADMIN
                    || $verifiedRoles->contains($role->value)
                );

                $requiresCode = $allowed
                    && $user->role === $role
                    && ! $verifiedRoles->contains($role->value);
            }

            $tabStates[$key] = [
                'label' => $config['label'],
                'role' => $role,
                'allowed' => $allowed,
                'unlocked' => $unlocked,
                'requires_code' => $requiresCode,
            ];
        }

        $defaultTab = $request->input('tab', collect($tabStates)->filter(fn ($tab) => $tab['unlocked'])->keys()->first() ?? 'none');

        $accessCodeRole = collect($tabStates)
            ->first(fn ($tab) => $tab['requires_code'])['role'] ?? null;

        $baseQuery = null;
        $shouldLoadInvoices = collect($tabStates)->contains(fn ($tab) => $tab['unlocked']);

        if ($shouldLoadInvoices) {
            $baseQuery = Invoice::query()
                ->with('customerService');
            
            // Sorting: default terbaru ke terlama berdasarkan created_at
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            
            // Validasi sort_by untuk keamanan
            $allowedSortColumns = ['created_at', 'updated_at', 'issue_date', 'due_date', 'total'];
            if (!in_array($sortBy, $allowedSortColumns)) {
                $sortBy = 'created_at';
            }
            
            // Validasi sort_order
            $sortOrder = strtolower($sortOrder) === 'asc' ? 'asc' : 'desc';
            
            $baseQuery->orderBy($sortBy, $sortOrder);

            if ($user->role !== Role::ADMIN && $user->role !== Role::ACCOUNTANT) {
                $baseQuery->where('user_id', $user->id);
            }
        }

        $needsConfirmationInvoicesQuery = $tabStates['needs-confirmation']['unlocked'] && $baseQuery
            ? (clone $baseQuery)
                ->where('needs_confirmation', true)
                ->whereNotNull('payment_proof_path')
            : null;

        if ($needsConfirmationInvoicesQuery) {
            $filterDate = $request->input('filter_date');
            $filterRange = $request->input('range');
            $filterType = $request->input('type');

            if ($filterDate) {
                $needsConfirmationInvoicesQuery->whereDate('issue_date', $filterDate);
            } elseif ($filterRange) {
                if ($filterRange === 'daily') {
                    $needsConfirmationInvoicesQuery->whereDate('issue_date', today());
                } elseif ($filterRange === 'weekly') {
                    $needsConfirmationInvoicesQuery->whereBetween('issue_date', [now()->startOfWeek(), now()->endOfWeek()]);
                } elseif ($filterRange === 'monthly') {
                    $needsConfirmationInvoicesQuery->whereMonth('issue_date', now()->month)->whereYear('issue_date', now()->year);
                }
            }

            if ($filterType === 'dp') {
                $needsConfirmationInvoicesQuery->whereNotNull('down_payment_due');
            } elseif ($filterType === 'lunas') {
                $needsConfirmationInvoicesQuery->whereNull('down_payment_due');
            }
            
            $needsConfirmationInvoices = $needsConfirmationInvoicesQuery->get();
        } else {
            $needsConfirmationInvoices = collect();
        }

        $settlementInvoices = $tabStates['settlement']['unlocked'] && $baseQuery
            ? (clone $baseQuery)
                ->whereIn('status', ['belum bayar', 'belum lunas'])
                ->where('type', '!=', Invoice::TYPE_SETTLEMENT)
                ->whereNotNull('settlement_token')
                ->where(function ($query) {
                    $query->whereNull('settlement_token_expires_at')
                        ->orWhere('settlement_token_expires_at', '>', now());
                })
                ->get()
            : collect();

        $historyInvoicesQuery = $tabStates['history']['unlocked'] && $baseQuery
            ? (clone $baseQuery)
            : null;

        if ($historyInvoicesQuery) {
            $filterDate = $request->input('filter_date');
            $filterRange = $request->input('range');
            $filterType = $request->input('type');

            if ($filterDate) {
                $historyInvoicesQuery->whereDate('issue_date', $filterDate);
            } elseif ($filterRange) {
                if ($filterRange === 'daily') {
                    $historyInvoicesQuery->whereDate('issue_date', today());
                } elseif ($filterRange === 'weekly') {
                    $historyInvoicesQuery->whereBetween('issue_date', [now()->startOfWeek(), now()->endOfWeek()]);
                } elseif ($filterRange === 'monthly') {
                    $historyInvoicesQuery->whereMonth('issue_date', now()->month)->whereYear('issue_date', now()->year);
                }
            }

            if ($filterType === 'dp') {
                $historyInvoicesQuery->whereNotNull('down_payment_due');
            } elseif ($filterType === 'lunas') {
                $historyInvoicesQuery->whereNull('down_payment_due');
            }
            
            $historyInvoices = $historyInvoicesQuery->get();
        } else {
            $historyInvoices = collect();
        }

        $incomeCategories = Category::where('type', 'pemasukan')
            ->orderBy('name')
            ->get();
            
        $filters = $request->only(['range', 'type', 'filter_date']);

        return view('invoices.index', compact(
            'needsConfirmationInvoices',
            'settlementInvoices',
            'historyInvoices',
            'incomeCategories',
            'tabStates',
            'defaultTab',
            'accessCodeRole',
            'filters'
        ));
    }

    public function create(): View
    {
        $incomeCategories = Category::where('type', 'pemasukan')->orderBy('name')->get();
        $passThroughPackages = $this->passThroughPackageManager->all();

        return view('invoices.create', compact('incomeCategories', 'passThroughPackages'));
    }

    public function createPublic(Request $request, PassThroughPackageManager $passThroughPackageManager): View
    {
        $incomeCategories = Category::where('type', 'pemasukan')->orderBy('name')->get();
        $passThroughPackages = $this->passThroughPackageManager->all();

        /** @var \App\Models\InvoicePortalPassphrase|null $passphrase */
        $passphrase = $request->attributes->get('invoicePortalPassphrase');
        $sessionData = (array) session('invoice_portal_passphrase');

        if ($passphrase) {
            if ((int) ($sessionData['id'] ?? 0) !== $passphrase->id) {
                $sessionData = [
                    'id' => $passphrase->id,
                    'token' => Crypt::encryptString((string) $passphrase->id),
                    'access_type' => $passphrase->access_type->value,
                    'access_label' => $passphrase->access_type->label(),
                    'label' => $passphrase->label,
                    'display_label' => $passphrase->displayLabel(),
                    'verified_at' => $sessionData['verified_at'] ?? now()->toIso8601String(),
                ];

                session(['invoice_portal_passphrase' => $sessionData]);
            }
        } else {
            $sessionData = [];
        }

    $allowedTransactionTypes = $passphrase?->allowedTransactionTypes() ?? [];
    $passphraseToken = $sessionData['token'] ?? null;

    return view('invoices.public-create', [
            'incomeCategories' => $incomeCategories,
            'passphraseSession' => $passphrase ? $sessionData : null,
            'allowedTransactionTypes' => $allowedTransactionTypes,
            'passphraseToken' => $passphraseToken,
            'passThroughPackages' => $passThroughPackages,
        ]);

    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $transactionType = $data['transaction_type'] ?? 'down_payment';
        $ownerId = auth()->id();

        $invoice = null;

        if ($transactionType === 'pass_through') {
            try {
                $context = $this->resolvePassThroughPackageContext($data);
            } catch (\RuntimeException $exception) {
                return back()
                    ->withErrors(['pass_through_package_id' => $exception->getMessage()])
                    ->withInput();
            }

            /** @var PassThroughPackage $package */
            $package = $context['package'];
            $quantity = max((int) ($data['pass_through_quantity'] ?? 1), 1);

            $description = trim((string) ($data['pass_through_description'] ?? ''));
            if ($description === '') {
                $description = $context['description'];
            }

            $durationDays = (int) ($context['duration_days'] ?? 0);
            if ($durationDays <= 0) {
                $durationDays = $package->durationDays;
            }

            $dailyBalanceUnit = max((float) ($context['daily_balance_unit'] ?? $package->dailyBalance ?? 0), 0.0);
            $maintenanceUnit = max((float) ($context['maintenance_unit'] ?? $package->maintenanceFee ?? 0), 0.0);
            $accountCreationUnit = $package->customerType === PassThroughPackage::CUSTOMER_TYPE_NEW
                ? max((float) ($context['account_creation_unit'] ?? $package->accountCreationFee ?? 0), 0.0)
                : 0.0;

            $dailyBalanceUnit = round($dailyBalanceUnit, 2);
            $adBudgetUnit = round($dailyBalanceUnit * $durationDays, 2);
            $maintenanceUnit = round($maintenanceUnit, 2);
            $accountCreationUnit = round($accountCreationUnit, 2);

            $adBudgetTotal = round($adBudgetUnit * $quantity, 2);
            $maintenanceTotal = round($maintenanceUnit * $quantity, 2);
            $accountCreationTotal = round($accountCreationUnit * $quantity, 2);
            $dailyBalanceTotal = round($dailyBalanceUnit * $quantity, 2);

            try {
                $invoice = $this->passThroughInvoiceCreator->create(
                    $package,
                    $quantity,
                    [
                        'description' => $description,
                        'ad_budget_unit' => $adBudgetUnit,
                        'ad_budget_total' => $adBudgetTotal,
                        'maintenance_unit' => $maintenanceUnit,
                        'maintenance_total' => $maintenanceTotal,
                        'account_creation_unit' => $accountCreationUnit,
                        'account_creation_total' => $accountCreationTotal,
                        'daily_balance_unit' => $dailyBalanceUnit,
                        'daily_balance_total' => $dailyBalanceTotal,
                        'duration_days' => $durationDays,
                        'owner_id' => $ownerId,
                        'created_by' => auth()->id(),
                        'customer_service_id' => null,
                        'customer_service_name' => auth()->user()?->name,
                        'client_name' => $data['client_name'],
                        'client_whatsapp' => $data['client_whatsapp'],
                        'client_address' => $data['client_address'] ?? null,
                        'due_date' => $data['due_date'] ?? null,
                        'debt_user_id' => $ownerId,
                    ]
                );
            } catch (\RuntimeException $exception) {
                return back()
                    ->withErrors(['pass_through_package_id' => $exception->getMessage()])
                    ->withInput();
            }
        } elseif ($transactionType === 'settlement') {
            $referenceInvoice = $request->referenceInvoice()
                ?? Invoice::where('number', $data['settlement_invoice_number'])->firstOrFail();

            $invoice = $this->persistSettlementInvoice($data, $referenceInvoice);
        } else {
            $data['customer_service_name'] = auth()->user()?->name;
            $invoice = $this->persistInvoice($data, $ownerId);
        }

        if ($invoice) {
            try {
                $this->regenerateInvoicePdf($invoice);
            } catch (Throwable $exception) {
                \Log::error('Failed to generate PDF for invoice after creation', [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->number,
                    'error' => $exception->getMessage(),
                ]);
                // Continue even if PDF generation fails
            }
        }

        return redirect()->route('invoices.index');
    }

    public function storePublic(
        PublicStoreInvoiceRequest $request
    )
    {
        $data = $request->validated();

        $passphrase = $request->passphrase();

        if (! $passphrase) {
            abort(403, 'Passphrase portal invoice tidak valid.');
        }

        $ownerId = User::where('role', Role::ADMIN)->orderBy('id')->value('id');

        if (! $ownerId) {
            return back()
                ->withErrors(['transaction_type' => 'Invoice belum dapat dibuat saat ini.'])
                ->withInput();
        }

        $data['customer_service_name'] = $passphrase->displayLabel();

        $customerServiceId = CustomerService::query()
            ->where('user_id', $passphrase->created_by)
            ->value('id');

        if ($customerServiceId) {
            $data['customer_service_id'] = $customerServiceId;
        }

        $data['created_by'] = $passphrase->created_by;
        $data['passphrase_access_type'] = $passphrase->access_type->value;

        // Process payment proof file if present
        $proofFile = $request->file('payment_proof');
        if ($proofFile) {
            $disk = 'public';
            $directory = 'invoice-proofs/' . now()->format('Y/m');
            $extension = strtolower($proofFile->getClientOriginalExtension() ?: $proofFile->extension() ?: 'png');
            $filename = 'payment-proof-' . Str::uuid()->toString() . '.' . $extension;
            $path = trim($directory . '/' . $filename, '/');

            Storage::disk($disk)->putFileAs($directory, $proofFile, $filename);

            $data['payment_proof_disk'] = $disk;
            $data['payment_proof_path'] = $path;
            $data['payment_proof_filename'] = $filename;
            $data['payment_proof_original_name'] = $proofFile->getClientOriginalName();
            $data['payment_proof_uploaded_at'] = now();
        }

        $transactionType = $data['transaction_type'] ?? 'down_payment';

        if ($transactionType === 'pass_through') {
            try {
                $context = $this->resolvePassThroughPackageContext($data);
            } catch (\RuntimeException $exception) {
                return back()
                    ->withErrors(['pass_through_package_id' => $exception->getMessage()])
                    ->withInput();
            }

            /** @var PassThroughPackage $package */
            $package = $context['package'];
            $quantity = max((int) ($data['pass_through_quantity'] ?? 1), 1);

            $description = trim((string) ($data['pass_through_description'] ?? ''));
            if ($description === '') {
                $description = $context['description'];
            }

            $durationDays = (int) ($context['duration_days'] ?? 0);
            if ($durationDays <= 0) {
                $durationDays = $package->durationDays;
            }

            $dailyBalanceUnit = max((float) ($context['daily_balance_unit'] ?? $package->dailyBalance ?? 0), 0.0);
            $maintenanceUnit = max((float) ($context['maintenance_unit'] ?? $package->maintenanceFee ?? 0), 0.0);
            $accountCreationUnit = $package->customerType === PassThroughPackage::CUSTOMER_TYPE_NEW
                ? max((float) ($context['account_creation_unit'] ?? $package->accountCreationFee ?? 0), 0.0)
                : 0.0;

            $dailyBalanceUnit = round($dailyBalanceUnit, 2);
            $adBudgetUnit = round($dailyBalanceUnit * $durationDays, 2);
            $maintenanceUnit = round($maintenanceUnit, 2);
            $accountCreationUnit = round($accountCreationUnit, 2);

            $adBudgetTotal = round($adBudgetUnit * $quantity, 2);
            $maintenanceTotal = round($maintenanceUnit * $quantity, 2);
            $accountCreationTotal = round($accountCreationUnit * $quantity, 2);
            $dailyBalanceTotal = round($dailyBalanceUnit * $quantity, 2);

            try {
                $invoice = $this->passThroughInvoiceCreator->create(
                    $package,
                    $quantity,
                    [
                        'description' => $description,
                        'ad_budget_unit' => $adBudgetUnit,
                        'ad_budget_total' => $adBudgetTotal,
                        'maintenance_unit' => $maintenanceUnit,
                        'maintenance_total' => $maintenanceTotal,
                        'account_creation_unit' => $accountCreationUnit,
                        'account_creation_total' => $accountCreationTotal,
                        'daily_balance_unit' => $dailyBalanceUnit,
                        'daily_balance_total' => $dailyBalanceTotal,
                        'duration_days' => $durationDays,
                        'owner_id' => $ownerId,
                        'created_by' => $passphrase->created_by,
                        'customer_service_id' => $customerServiceId,
                        'customer_service_name' => $data['customer_service_name'],
                        'client_name' => $data['client_name'],
                        'client_whatsapp' => $data['client_whatsapp'],
                        'client_address' => $data['client_address'] ?? null,
                        'due_date' => $data['due_date'] ?? null,
                        'debt_user_id' => $passphrase->created_by ?: $ownerId,
                    ]
                );
            } catch (\RuntimeException $exception) {
                return back()
                    ->withErrors(['pass_through_package_id' => $exception->getMessage()])
                    ->withInput();
            }
        } elseif ($data['transaction_type'] === 'settlement') {
            $referenceInvoice = $request->referenceInvoice()
                ?? Invoice::where('number', $data['settlement_invoice_number'])->firstOrFail();

            $invoice = $this->persistSettlementInvoice($data, $referenceInvoice);
        } else {
            $invoice = $this->persistInvoice($data, $ownerId);
        }

        if ($invoice) {
            try {
                $this->regenerateInvoicePdf($invoice);
            } catch (Throwable $exception) {
                \Log::error('Failed to generate PDF for public invoice after creation', [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->number,
                    'error' => $exception->getMessage(),
                ]);
                // Continue even if PDF generation fails
            }
        }

        $passphrase->markAsUsed($request->ip(), $request->userAgent(), 'submission');

        $previewUrl = null;

        if ($invoice) {
            try {
                $this->invoicePdfService->ensureStoredPdfPath($invoice);
                $previewUrl = route('invoices.public.pdf-hosted', ['token' => $invoice->public_token]);
            } catch (Throwable $exception) {
                \Log::error('Failed to prepare PDF preview for public invoice', [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->number,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $statusMessage = $previewUrl
            ? 'Invoice berhasil dibuat. Gunakan tautan di bawah ini untuk membuka pratinjau PDF.'
            : 'Invoice berhasil dibuat, tetapi pratinjau PDF belum tersedia. Silakan coba lagi nanti.';

        $redirect = redirect()
            ->route('invoices.public.create')
            ->with('status', $statusMessage)
            ->with('active_portal_tab', 'create_invoice');

        if ($invoice) {
            $redirect->with('invoice_number', $invoice->number);
        }

        if ($previewUrl) {
            $redirect->with('invoice_pdf_url', $previewUrl);
        }

        return $redirect;
    }

    public function confirmPublicPayment(PublicConfirmPaymentRequest $request): RedirectResponse
    {
        $invoice = $request->invoice();
        $passphrase = $request->passphrase();

        if (! $invoice || ! $passphrase) {
            abort(403, 'Permintaan tidak valid.');
        }

        $metadataUpdates = [];
        $creatorId = $passphrase->created_by;

        if (! $invoice->created_by && $creatorId) {
            $metadataUpdates['created_by'] = $creatorId;
        }

        $customerServiceId = $passphrase->creatorCustomerServiceId();

        if ($customerServiceId && ! $invoice->customer_service_id) {
            $metadataUpdates['customer_service_id'] = $customerServiceId;
        }

        if (! $invoice->customer_service_name || $passphrase->labelMatches($invoice->customer_service_name)) {
            $metadataUpdates['customer_service_name'] = $passphrase->displayLabel();
        }

        $proofFile = $request->file('payment_proof');

        $disk = 'public';
        $directory = 'invoice-proofs/' . now()->format('Y/m');
        $extension = strtolower($proofFile->getClientOriginalExtension() ?: $proofFile->extension() ?: 'png');
        $filename = 'payment-proof-' . Str::uuid()->toString() . '.' . $extension;
        $path = trim($directory . '/' . $filename, '/');

        if ($invoice->payment_proof_path && $invoice->payment_proof_disk) {
            Storage::disk($invoice->payment_proof_disk)->delete($invoice->payment_proof_path);
        }

        Storage::disk($disk)->putFileAs($directory, $proofFile, $filename);

        $invoice->forceFill(array_merge($metadataUpdates, [
            'payment_proof_disk' => $disk,
            'payment_proof_path' => $path,
            'payment_proof_filename' => $filename,
            'payment_proof_original_name' => $proofFile->getClientOriginalName(),
            'payment_proof_uploaded_at' => now(),
            'status' => 'belum lunas',
            'needs_confirmation' => true,
        ]))->save();

        $passphrase->markAsUsed($request->ip(), $request->userAgent(), 'payment_confirmation');

        return redirect()
            ->route('invoices.public.create')
            ->with('status', 'Bukti pembayaran berhasil dikirim. Tim akuntansi akan memverifikasi dalam waktu dekat.')
            ->with('active_portal_tab', 'confirm_payment')
            ->with('confirmed_invoice_summary', $this->makeInvoiceReferencePayload($invoice));
    }

    public function showPaymentProof(Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);

        if (! $invoice->hasPaymentProof()) {
            abort(Response::HTTP_NOT_FOUND, 'Bukti pembayaran tidak ditemukan.');
        }

        $disk = $invoice->payment_proof_disk ?: config('filesystems.default');
        $storage = Storage::disk($disk);

        if (! $storage->exists($invoice->payment_proof_path)) {
            abort(Response::HTTP_NOT_FOUND, 'Bukti pembayaran tidak ditemukan.');
        }

        try {
            $contents = $storage->get($invoice->payment_proof_path);
        } catch (Throwable $exception) {
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Bukti pembayaran tidak dapat diakses.');
        }

        $mimeType = $storage->mimeType($invoice->payment_proof_path) ?: 'application/octet-stream';
        $filename = $invoice->payment_proof_filename ?? basename($invoice->payment_proof_path);

        return response($contents, Response::HTTP_OK, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . addslashes($filename) . '"',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    public function publicReference(Request $request, string $number): JsonResponse
    {
        /** @var \App\Models\InvoicePortalPassphrase|null $passphrase */
        $passphrase = $request->attributes->get('invoicePortalPassphrase');

        if (! $passphrase || ! in_array('settlement', $passphrase->allowedTransactionTypes(), true)) {
            return response()->json([
                'message' => 'Passphrase tidak memiliki izin untuk melihat data invoice.',
            ], Response::HTTP_FORBIDDEN);
        }

        $invoice = Invoice::query()
            ->where('number', $number)
            ->where('type', '!=', Invoice::TYPE_SETTLEMENT)
            ->first();

        if (! $invoice) {
            return response()->json([
                'message' => 'Invoice referensi tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json($this->makeInvoiceReferencePayload($invoice));
    }

    public function reference(Request $request, string $number): JsonResponse
    {
        $invoice = Invoice::query()
            ->where('number', $number)
            ->where('type', '!=', Invoice::TYPE_SETTLEMENT)
            ->first();

        if (! $invoice) {
            return response()->json([
                'message' => 'Invoice referensi tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        $this->authorize('view', $invoice);

        return response()->json($this->makeInvoiceReferencePayload($invoice));
    }

    public function send(Invoice $invoice): RedirectResponse
    {
        $this->authorize('send', $invoice);

        $invoice->update(['status' => 'belum bayar']);
        return redirect()->route('invoices.index');
    }

    public function storePayment(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('storePayment', $invoice);

        $invoice->loadMissing('items');

        $invoiceTotal = round((float) $invoice->total, 2);
        $currentDownPayment = round((float) $invoice->down_payment, 2);
        $remainingBalance = max($invoiceTotal - $currentDownPayment, 0);
        
        if ($invoice->status === 'lunas' || $remainingBalance <= 0) {
            DB::transaction(function () use ($invoice) {
                $invoice->loadMissing('items', 'debt');
                
                if (!$invoice->debt) {
                    $isPassThrough = in_array($invoice->type, [
                        Invoice::TYPE_PASS_THROUGH_NEW,
                        Invoice::TYPE_PASS_THROUGH_EXISTING
                    ], true);
                    
                    $relatedParty = $invoice->client_name
                        ?: ($invoice->client_whatsapp ?: 'Klien Invoice #' . $invoice->number);
                    
                    if ($isPassThrough) {
                        $adBudgetItem = $invoice->items->first(function ($item) {
                            return strpos($item->description, 'Dana Invoices Iklan') !== false;
                        });
                        
                        $adBudgetTotal = $adBudgetItem 
                            ? round($adBudgetItem->price * $adBudgetItem->quantity, 2)
                            : $invoice->total;
                        
                        $description = $adBudgetItem?->description ?? '';
                        $durationDays = 1;
                        if (preg_match('/(\d+)\s*hari/i', $description, $matches)) {
                            $durationDays = max(1, (int) $matches[1]);
                        }
                        
                        $dailyBalanceTotal = $durationDays > 0 
                            ? round($adBudgetTotal / $durationDays, 2) 
                            : 0;
                        
                        Debt::create([
                            'invoice_id' => $invoice->id,
                            'description' => $invoice->transactionDescription(),
                            'related_party' => $relatedParty,
                            'type' => Debt::TYPE_PASS_THROUGH,
                            'amount' => $adBudgetTotal,
                            'due_date' => $invoice->due_date,
                            'status' => Debt::STATUS_BELUM_LUNAS,
                            'user_id' => $invoice->user_id,
                            'daily_deduction' => $dailyBalanceTotal,
                        ]);
                    } else {
                        $firstItem = $invoice->items()->first();
                        $categoryId = $firstItem?->category_id;
                        
                        $debt = Debt::create([
                            'invoice_id' => $invoice->id,
                            'description' => $invoice->transactionDescription(),
                            'related_party' => $relatedParty,
                            'type' => Debt::TYPE_DOWN_PAYMENT,
                            'amount' => $invoice->total,
                            'due_date' => $invoice->due_date,
                            'status' => $invoice->status === 'lunas' ? Debt::STATUS_LUNAS : Debt::STATUS_BELUM_LUNAS,
                            'user_id' => $invoice->user_id,
                            'category_id' => $categoryId,
                        ]);
                        
                        if ($invoice->down_payment > 0) {
                            $debt->payments()->create([
                                'amount' => $invoice->down_payment,
                                'payment_date' => $invoice->payment_date ?? now(),
                                'notes' => 'Down payment invoice #' . $invoice->number,
                            ]);
                        }
                    }
                } else {
                    if ($invoice->status === 'lunas') {
                        $invoice->debt->forceFill([
                            'status' => Debt::STATUS_LUNAS,
                        ])->save();
                    }
                }
                
                $invoice->needs_confirmation = false;
                $invoice->save();
            });
            
            return redirect()->route('invoices.index')->with('success', 'Invoice berhasil dikonfirmasi.');
        }

        $request->validate([
            'payment_amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $paymentAmount = round((float) $request->input('payment_amount'), 2);
        $paymentDate = $request->input('payment_date');
        $categoryId = $request->input('category_id');
        
        $isConfirmingExistingDownPayment = $currentDownPayment > 0 && abs($paymentAmount - $currentDownPayment) < 0.01;
        
        if ($isConfirmingExistingDownPayment && $currentDownPayment >= $invoiceTotal && $invoiceTotal > 0) {
            DB::transaction(function () use ($invoice, $paymentDate) {
                $invoice->loadMissing('items', 'debt');
                
                $relatedParty = $invoice->client_name
                    ?: ($invoice->client_whatsapp ?: 'Klien Invoice #' . $invoice->number);
                
                if (!$invoice->debt) {
                    $firstItem = $invoice->items()->first();
                    $categoryId = $firstItem?->category_id;
                    
                    $debt = Debt::create([
                        'invoice_id' => $invoice->id,
                        'description' => $invoice->transactionDescription(),
                        'related_party' => $relatedParty,
                        'type' => Debt::TYPE_DOWN_PAYMENT,
                        'amount' => $invoice->total,
                        'due_date' => $invoice->due_date,
                        'status' => Debt::STATUS_LUNAS,
                        'user_id' => $invoice->user_id,
                        'category_id' => $categoryId,
                    ]);
                    
                    if ($invoice->down_payment > 0) {
                        $debt->payments()->create([
                            'amount' => $invoice->down_payment,
                            'payment_date' => $paymentDate,
                            'notes' => 'Konfirmasi down payment invoice #' . $invoice->number,
                        ]);
                    }
                } else {
                    $invoice->debt->forceFill([
                        'status' => Debt::STATUS_LUNAS,
                    ])->save();
                    
                    $invoice->debt->load('payments');
                    $existingPaymentsTotal = $invoice->debt->payments->sum('amount');
                    if ($existingPaymentsTotal < $invoice->down_payment) {
                        $invoice->debt->payments()->create([
                            'amount' => $invoice->down_payment - $existingPaymentsTotal,
                            'payment_date' => $paymentDate,
                            'notes' => 'Konfirmasi down payment invoice #' . $invoice->number,
                        ]);
                    }
                }
                
                $invoice->forceFill([
                    'status' => 'lunas',
                    'needs_confirmation' => false,
                    'payment_date' => $paymentDate,
                ])->save();
                
                $invoice->load('debt');
                $categoryId = $invoice->debt->category_id;
                if (!$categoryId) {
                    $firstItem = $invoice->items()->first();
                    if ($firstItem && $firstItem->category_id) {
                        $categoryId = $firstItem->category_id;
                        $invoice->debt->category_id = $categoryId;
                        $invoice->debt->save();
                    }
                }
                
                if ($categoryId) {
                    \App\Models\Transaction::create([
                        'category_id' => $categoryId,
                        'user_id' => auth()->id(),
                        'amount' => $invoice->total,
                        'description' => $invoice->transactionDescription(),
                        'date' => $paymentDate,
                    ]);
                }
            });
            
            return redirect()->route('invoices.index')->with('success', 'Invoice berhasil dikonfirmasi dan dilunasi.');
        }
        
        $willBePaidOff = $invoiceTotal > 0 && round($currentDownPayment + $paymentAmount, 2) >= $invoiceTotal;

        if ($willBePaidOff && empty($categoryId)) {
            $categoryId = $invoice->items->first()?->category_id;

            if (empty($categoryId)) {
                return back()
                    ->withErrors(['category_id' => 'Pilih kategori pemasukan untuk pembayaran lunas.'])
                    ->withInput();
            }
        }

        if ($paymentAmount > $remainingBalance) {
            return back()
                ->withErrors(['payment_amount' => 'Nominal pembayaran melebihi sisa tagihan.'])
                ->withInput();
        }

        $categoryId = $categoryId ? (int) $categoryId : null;

        DB::transaction(function () use ($invoice, $paymentAmount, $paymentDate, $categoryId, $willBePaidOff, $isConfirmingExistingDownPayment) {
            $invoice->loadMissing('items', 'debt.payments');

            $wasNeedingConfirmation = $invoice->needs_confirmation;

            $relatedParty = $invoice->client_name
                ?: ($invoice->client_whatsapp ?: 'Klien Invoice #' . $invoice->number);
            $paymentNotes = $willBePaidOff
                ? 'Pelunasan invoice #' . $invoice->number
                : 'Pembayaran down payment invoice #' . $invoice->number;

            $isPassThrough = in_array($invoice->type, [
                Invoice::TYPE_PASS_THROUGH_NEW,
                Invoice::TYPE_PASS_THROUGH_EXISTING
            ], true);

            $adBudgetTotal = null;
            $dailyBalanceTotal = null;

            if ($isPassThrough) {
                $adBudgetItem = $invoice->items->first(function ($item) {
                    return strpos($item->description, 'Dana Invoices Iklan') !== false;
                });

                if (!$adBudgetItem) {
                    $adBudgetTotal = $invoice->total;
                    $dailyBalanceTotal = 0;
                } else {
                    $adBudgetTotal = round($adBudgetItem->price * $adBudgetItem->quantity, 2);
                    
                    $description = $adBudgetItem->description;
                    $durationDays = 1;
                    if (preg_match('/(\d+)\s*hari/i', $description, $matches)) {
                        $durationDays = max(1, (int) $matches[1]);
                    }
                    
                    $dailyBalanceTotal = $durationDays > 0 
                        ? round($adBudgetTotal / $durationDays, 2) 
                        : 0;
                }
                
                $debt = Debt::updateOrCreate(
                    ['invoice_id' => $invoice->id],
                    [
                        'description' => $invoice->transactionDescription(),
                        'related_party' => $relatedParty,
                        'type' => Debt::TYPE_PASS_THROUGH,
                        'amount' => $adBudgetTotal,
                        'due_date' => $invoice->due_date,
                        'status' => Debt::STATUS_BELUM_LUNAS,
                        'user_id' => $invoice->user_id,
                        'daily_deduction' => $dailyBalanceTotal,
                    ]
                );
            } else {
                $debt = Debt::updateOrCreate(
                    ['invoice_id' => $invoice->id],
                    [
                        'description' => $invoice->transactionDescription(),
                        'related_party' => $relatedParty,
                        'type' => Debt::TYPE_DOWN_PAYMENT,
                        'amount' => $invoice->total,
                        'due_date' => $invoice->due_date,
                        'status' => Debt::STATUS_BELUM_LUNAS,
                        'user_id' => $invoice->user_id,
                    ]
                );

                if ($debt->wasRecentlyCreated && !$debt->category_id) {
                    $firstItem = $invoice->items()->first();
                    if ($firstItem && $firstItem->category_id) {
                        $debt->category_id = $firstItem->category_id;
                        $debt->save();
                    }
                }
            }

            if ($debt && !$isPassThrough && !$isConfirmingExistingDownPayment) {
                $debt->payments()->create([
                    'amount' => $paymentAmount,
                    'payment_date' => $paymentDate,
                    'notes' => $paymentNotes,
                ]);

                $debt->load('payments');
            } elseif ($debt && !$isPassThrough && $isConfirmingExistingDownPayment) {
                $debt->load('payments');
                $existingPaymentsTotal = $debt->payments->sum('amount');
                if ($existingPaymentsTotal < $paymentAmount) {
                    $debt->payments()->create([
                        'amount' => $paymentAmount - $existingPaymentsTotal,
                        'payment_date' => $paymentDate,
                        'notes' => 'Konfirmasi down payment invoice #' . $invoice->number,
                    ]);
                    $debt->load('payments');
                }
            }

            if ($isPassThrough) {
                $invoice->down_payment = $invoice->total;
                $invoice->payment_date = $paymentDate;
                $invoice->needs_confirmation = false;
                $invoice->status = 'lunas';
            } else {
                $downPaymentTotal = $debt?->payments->sum('amount') ?? 0;
                $invoice->down_payment = min($invoice->total, $downPaymentTotal);
                $invoice->payment_date = $paymentDate;
                $invoice->needs_confirmation = false;

                if ($invoice->down_payment >= $invoice->total && $invoice->total > 0) {
                    $invoice->status = 'lunas';
                } elseif ($invoice->down_payment > 0) {
                    $invoice->status = 'belum lunas';
                } else {
                    $invoice->status = 'belum bayar';
                }
            }

            $invoice->save();

            if ($isPassThrough && $wasNeedingConfirmation) {
                $incomeCategoryId = $invoice->items->first()?->category_id;
                if ($incomeCategoryId) {
                    $firstItem = $invoice->items->first();
                    $quantity = $firstItem ? max(1, (int) $firstItem->quantity) : 1;
                    
                    $clientInfo = $invoice->client_name ?: $invoice->client_whatsapp ?: 'Klien';
                    $description = 'Invoices Iklan' . ($quantity > 1 ? ' (x' . $quantity . ')' : '') . ' - ' . $clientInfo . ' (' . $invoice->number . ')';
                    
                    Transaction::create([
                        'category_id' => $incomeCategoryId,
                        'user_id' => $invoice->user_id,
                        'amount' => $invoice->total,
                        'description' => $description,
                        'date' => $paymentDate,
                    ]);
                }
            }

            if ($debt) {
                if ($isPassThrough) {
                    $debt->status = Debt::STATUS_BELUM_LUNAS;
                    if (isset($adBudgetTotal)) {
                        $debt->amount = $adBudgetTotal;
                    }
                } else {
                    $debt->status = $invoice->status === 'lunas'
                        ? Debt::STATUS_LUNAS
                        : Debt::STATUS_BELUM_LUNAS;
                    $debt->amount = $invoice->total;
                }
                $debt->description = $invoice->transactionDescription();
                $debt->due_date = $invoice->due_date;
                $debt->related_party = $relatedParty;
                $debt->save();
            }

            if ($willBePaidOff && !$isPassThrough) {
                Transaction::create([
                    'category_id' => $categoryId,
                    'user_id' => auth()->id(),
                    'amount' => $invoice->total,
                    'description' => $invoice->transactionDescription(),
                    'date' => $paymentDate,
                ]);
            }
        });

        return redirect()->route('invoices.index')->with('success', 'Payment recorded successfully.');
    }

    public function refreshSettlementToken(Request $request, Invoice $invoice, InvoiceSettlementService $service): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $data = $request->validate([
            'expires_at' => 'nullable|date|after:now',
        ]);

        $service->refreshToken($invoice, $data['expires_at'] ?? null);

        return back()->with('status', 'Token pelunasan berhasil diperbarui.');
    }

    public function revokeSettlementToken(Invoice $invoice, InvoiceSettlementService $service): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $service->revokeToken($invoice);

        return back()->with('status', 'Token pelunasan berhasil dicabut.');
    }

    public function pdfHosted(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);

        $invoice->loadMissing('items', 'customerService');
        
        $companyInfo = $this->vodecoWebsiteService->getCompanyInfo();
        $paymentMethods = $this->vodecoWebsiteService->getPaymentMethods();

        $data = $this->invoiceViewService->prepareInvoiceData($invoice, $companyInfo, $paymentMethods);

        return view('invoices.page', $data);
    }

    public function showPublicHosted(string $token): View
    {
        $invoice = Invoice::where('public_token', $token)
            ->with('items', 'customerService')
            ->firstOrFail();
        
        $companyInfo = $this->vodecoWebsiteService->getCompanyInfo();
        $paymentMethods = $this->vodecoWebsiteService->getPaymentMethods();

        $data = $this->invoiceViewService->prepareInvoiceData($invoice, $companyInfo, $paymentMethods);

        return view('invoices.page', $data);
    }

    public function show(Invoice $invoice)
    {
        try {
            $this->authorize('view', $invoice);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            abort(403, 'Anda tidak memiliki izin untuk melihat invoice ini.');
        }

        $invoice->loadMissing('items', 'customerService');

        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $incomeCategories = Category::where('type', 'pemasukan')->orderBy('name')->get();

        $invoice->loadMissing('items');

        return view('invoices.edit', compact('invoice', 'incomeCategories'));
    }

    public function update(StoreInvoiceRequest $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $data = $request->validated();

        $ownerId = $invoice->user_id;
        $previousPdfPath = $invoice->pdf_path;

        DB::transaction(function () use ($invoice, $data, $ownerId) {
            $items = $this->mapInvoiceItems($data['items']);

            $invoice->update([
                'user_id' => $ownerId,
                'customer_service_id' => null,
                'customer_service_name' => $invoice->customer_service_name ?? auth()->user()?->name,
                'client_name' => $data['client_name'],
                'client_whatsapp' => $data['client_whatsapp'],
                'client_address' => $data['client_address'],
                'issue_date' => $data['issue_date'] ?? $invoice->issue_date ?? now(),
                'due_date' => $data['due_date'] ?? null,
                'total' => $this->calculateInvoiceTotal($items),
                'down_payment_due' => array_key_exists('down_payment_due', $data)
                    ? (isset($data['down_payment_due']) ? (float) $data['down_payment_due'] : null)
                    : $invoice->down_payment_due,
            ]);

            $invoice->items()->delete();

            foreach ($items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'category_id' => $item['category_id'],
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }

            $invoice->load('items');
            $this->syncDebtAfterInvoiceChange($invoice);
        });

        $invoice->refresh()->load('items', 'customerService');
        $this->regenerateInvoicePdf($invoice, $previousPdfPath);

        return redirect()->route('invoices.index');
    }

    public function destroy(Invoice $invoice)
    {
        $this->authorize('delete', $invoice);

        $invoiceNumber = $invoice->number;
        $invoice->delete();

        return redirect()->route('invoices.index', ['tab' => 'history'])
            ->with('success', "Invoice #{$invoiceNumber} berhasil dihapus.");
    }

    private function resolvePassThroughPackageContext(array $data): array
    {
        $packageId = $data['pass_through_package_id'] ?? null;

        if ($packageId === 'custom') {
            $customerTypeInput = strtolower((string) ($data['pass_through_custom_customer_type'] ?? ''));
            $customerType = in_array($customerTypeInput, [PassThroughPackage::CUSTOMER_TYPE_EXISTING, PassThroughPackage::CUSTOMER_TYPE_NEW], true)
                ? $customerTypeInput
                : PassThroughPackage::CUSTOMER_TYPE_NEW;

            $dailyBalance = max((float) ($data['pass_through_custom_daily_balance'] ?? 0), 0.0);
            $durationDays = max((int) ($data['pass_through_custom_duration_days'] ?? 0), 0);
            $maintenanceFee = max((float) ($data['pass_through_custom_maintenance_fee'] ?? 0), 0.0);
            $accountCreationFee = $customerType === PassThroughPackage::CUSTOMER_TYPE_NEW
                ? max((float) ($data['pass_through_custom_account_creation_fee'] ?? 0), 0.0)
                : 0.0;

            $package = new PassThroughPackage([
                'name' => 'Paket Custom',
                'customer_type' => $customerType,
                'daily_balance' => $dailyBalance,
                'duration_days' => $durationDays,
                'maintenance_fee' => $maintenanceFee,
                'account_creation_fee' => $accountCreationFee,
            ]);

            $description = trim((string) ($data['pass_through_description'] ?? ''));
            if ($description === '') {
                $description = 'Paket Custom';
            }

            return [
                'package' => $package,
                'description' => $description,
                'duration_days' => $durationDays,
                'daily_balance_unit' => $dailyBalance,
                'maintenance_unit' => $maintenanceFee,
                'account_creation_unit' => $accountCreationFee,
            ];
        }

        $package = $packageId ? $this->passThroughPackageManager->find($packageId) : null;

        if (! $package) {
            throw new \RuntimeException('Paket Invoices Iklan tidak ditemukan.');
        }

        $description = trim((string) ($data['pass_through_description'] ?? ''));
        if ($description === '') {
            $description = $package->name;
        }

        $durationDays = (int) ($data['pass_through_duration_days'] ?? $package->durationDays ?? 0);
        if ($durationDays <= 0) {
            $durationDays = $package->durationDays;
        }

        $maintenanceUnit = (float) $package->maintenanceFee;
        $accountCreationUnit = $package->customerType === PassThroughPackage::CUSTOMER_TYPE_NEW
            ? (float) $package->accountCreationFee
            : 0.0;

        return [
            'package' => $package,
            'description' => $description,
            'duration_days' => $durationDays,
            'daily_balance_unit' => (float) $package->dailyBalance,
            'maintenance_unit' => $maintenanceUnit,
            'account_creation_unit' => $accountCreationUnit,
        ];
    }

    private function regenerateInvoicePdf(Invoice $invoice, ?string $previousPath = null): void
    {
        $strategy = config('pdf.generation.strategy', 'on_demand');

        if ($strategy === 'on_demand') {
            $this->invoicePdfService->invalidateCache($invoice);
            return;
        }

        $disk = Storage::disk('public');

        $pathToDelete = $previousPath ?? $invoice->pdf_path;

        if ($pathToDelete) {
            $disk->delete($pathToDelete);
        }

        $newPath = $this->invoicePdfService->store($invoice);

        $invoice->forceFill(['pdf_path' => $newPath])->save();
    }

    private function persistInvoice(array $data, ?int $ownerId = null): Invoice
    {
        return DB::transaction(function () use ($data, $ownerId) {
            $transactionType = $data['transaction_type'] ?? 'down_payment';
            $items = $this->mapInvoiceItems($data['items'] ?? []);
            $invoiceNumber = $this->generateInvoiceNumber();
            $total = $this->calculateInvoiceTotal($items);
            $downPaymentDue = null;

            if ($transactionType === 'down_payment' && array_key_exists('down_payment_due', $data)) {
                $downPaymentDue = isset($data['down_payment_due']) ? (float) $data['down_payment_due'] : null;
            }

            // Untuk passphrase Admin Perpanjangan dan Admin Pelunasan,
            // invoice tidak langsung masuk ke "Menunggu Konfirmasi" saat dibuat.
            // Hanya masuk setelah ada bukti pembayaran.
            $passphraseAccessType = $data['passphrase_access_type'] ?? null;
            $isAdminPassphrase = in_array($passphraseAccessType, ['admin_perpanjangan', 'admin_pelunasan'], true);
            
            // Hanya set needs_confirmation jika:
            // 1. Transaction type adalah full_payment, DAN
            // 2. Bukan dari passphrase Admin Perpanjangan/Pelunasan, ATAU
            // 3. Sudah ada bukti pembayaran
            $hasPaymentProof = !empty($data['payment_proof_path']);
            $requiresConfirmation = $transactionType === 'full_payment' && (!$isAdminPassphrase || $hasPaymentProof);

            $status = match ($transactionType) {
                'down_payment' => 'belum lunas',
                'full_payment' => $requiresConfirmation ? 'belum lunas' : ($hasPaymentProof ? 'belum lunas' : 'belum bayar'),
                default => 'belum bayar',
            };

            $invoice = Invoice::create([
                'user_id' => $ownerId ?? auth()->id(),
                'created_by' => $data['created_by'] ?? auth()->id(),
                'customer_service_id' => $data['customer_service_id'] ?? null,
                'customer_service_name' => $data['customer_service_name'] ?? auth()->user()?->name,
                'client_name' => $data['client_name'],
                'client_whatsapp' => $data['client_whatsapp'],
                'client_address' => $data['client_address'],
                'number' => $invoiceNumber,
                'issue_date' => $data['issue_date'] ?? now(),
                'due_date' => $data['due_date'] ?? null,
                'status' => $status,
                'total' => $total,
                'type' => Invoice::TYPE_STANDARD,
                'reference_invoice_id' => null,
                'down_payment' => 0,
                'down_payment_due' => $downPaymentDue,
                'payment_date' => null,
                'needs_confirmation' => $requiresConfirmation,
                'payment_proof_disk' => $data['payment_proof_disk'] ?? null,
                'payment_proof_path' => $data['payment_proof_path'] ?? null,
                'payment_proof_filename' => $data['payment_proof_filename'] ?? null,
                'payment_proof_original_name' => $data['payment_proof_original_name'] ?? null,
                'payment_proof_uploaded_at' => $data['payment_proof_uploaded_at'] ?? null,
            ]);

            foreach ($items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'category_id' => $item['category_id'],
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }

            return $invoice->load('items', 'customerService');
        });
    }

    private function persistSettlementInvoice(array $data, Invoice $referenceInvoice): Invoice
    {
        return DB::transaction(function () use ($data, $referenceInvoice) {
            $invoiceNumber = $this->generateInvoiceNumber();
            $paidAmount = (float) $data['settlement_paid_amount'];
            $isPaidFull = $data['settlement_payment_status'] === 'paid_full';
            $now = now();

            $invoice = Invoice::create([
                'user_id' => $referenceInvoice->user_id,
                'created_by' => $data['created_by'] ?? auth()->id(),
                'customer_service_id' => $referenceInvoice->customer_service_id,
                'customer_service_name' => $referenceInvoice->customer_service_name,
                'client_name' => $referenceInvoice->client_name,
                'client_whatsapp' => $referenceInvoice->client_whatsapp,
                'client_address' => $referenceInvoice->client_address,
                'number' => $invoiceNumber,
                'issue_date' => $now,
                'due_date' => null,
                'status' => $isPaidFull ? 'lunas' : 'belum lunas',
                'total' => $paidAmount,
                'down_payment' => $paidAmount,
                'payment_date' => $now,
                'type' => Invoice::TYPE_SETTLEMENT,
                'reference_invoice_id' => $referenceInvoice->id,
                'payment_proof_disk' => $data['payment_proof_disk'] ?? null,
                'payment_proof_path' => $data['payment_proof_path'] ?? null,
                'payment_proof_filename' => $data['payment_proof_filename'] ?? null,
                'payment_proof_original_name' => $data['payment_proof_original_name'] ?? null,
                'payment_proof_uploaded_at' => $data['payment_proof_uploaded_at'] ?? null,
            ]);

            $referenceInvoice->loadMissing('items');
            $firstItem = $referenceInvoice->items->first();

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'category_id' => $firstItem?->category_id,
                'description' => 'Pelunasan Invoice #' . $referenceInvoice->number,
                'quantity' => 1,
                'price' => $paidAmount,
            ]);

            $updatedDownPayment = round((float) $referenceInvoice->down_payment, 2) + round($paidAmount, 2);
            $newDownPayment = min((float) $referenceInvoice->total, $updatedDownPayment);

            $referenceInvoice->forceFill([
                'down_payment' => $newDownPayment,
                'payment_date' => $now,
                'status' => $isPaidFull || $newDownPayment >= (float) $referenceInvoice->total ? 'lunas' : 'belum lunas',
            ])->save();

            return $invoice->load('items', 'customerService', 'referenceInvoice');
        });
    }

    private function makeInvoiceReferencePayload(Invoice $invoice): array
    {
        $remaining = max((float) $invoice->total - (float) $invoice->down_payment, 0);

        return [
            'number' => $invoice->number,
            'client_name' => $invoice->client_name,
            'client_whatsapp' => $invoice->client_whatsapp,
            'client_address' => $invoice->client_address,
            'due_date' => $invoice->due_date?->format('Y-m-d'),
            'total' => (float) $invoice->total,
            'down_payment' => (float) $invoice->down_payment,
            'remaining_balance' => $remaining,
            'status' => $invoice->status,
            'customer_service_name' => $invoice->customer_service_name,
            'payment_proof_uploaded_at' => $invoice->payment_proof_uploaded_at?->toIso8601String(),
        ];
    }

    private function generateInvoiceNumber(): string
    {
        $date = now()->format('Ymd');
        $count = Invoice::whereDate('created_at', today())->count();
        $sequence = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
        $random = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);

        return "{$date}-{$sequence}{$random}";
    }

    private function mapInvoiceItems(array $items): array
    {
        return collect($items)
            ->map(function ($item) {
                return [
                    'description' => $item['description'],
                    'quantity' => (int) $item['quantity'],
                    'price' => (float) $item['price'],
                    'category_id' => isset($item['category_id']) ? (int) $item['category_id'] : null,
                ];
            })
            ->values()
            ->all();
    }

    private function calculateInvoiceTotal(array $items): float
    {
        return collect($items)->sum(fn ($item) => $item['quantity'] * $item['price']);
    }

    private function syncDebtAfterInvoiceChange(Invoice $invoice): void
    {
        $debt = $invoice->debt;

        if (! $debt) {
            return;
        }

        $invoice->loadMissing('items');

        $relatedParty = $invoice->client_name
            ?: ($invoice->client_whatsapp ?: 'Klien Invoice #' . $invoice->number);

        $debt->description = $invoice->transactionDescription();
        $debt->amount = $invoice->total;
        $debt->due_date = $invoice->due_date;
        $debt->related_party = $relatedParty;
        $debt->save();
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $request->validate([
            'action' => ['required', 'string', 'in:approve,delete,send'],
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => ['required', 'integer', 'exists:invoices,id'],
            'tab' => ['nullable', 'string', 'in:needs-confirmation,history'],
        ]);

        $selectedIds = $request->input('selected');
        $action = $request->input('action');
        $tab = $request->input('tab', 'needs-confirmation');
        $user = $request->user();

        $invoices = Invoice::whereIn('id', $selectedIds)->get();

        // Filter invoices yang bisa diakses user
        $accessibleInvoices = $invoices->filter(function ($invoice) use ($user) {
            return Gate::allows('view', $invoice);
        });

        if ($accessibleInvoices->isEmpty()) {
            return redirect()->route('invoices.index', ['tab' => $tab])
                ->with('error', 'Tidak ada invoice yang dapat diakses.');
        }

        if ($action === 'approve') {
            // Set needs_confirmation = false untuk invoice yang dipilih
            $accessibleInvoices->each(function ($invoice) {
                $this->authorize('storePayment', $invoice);

                // Untuk invoice pass through (iklan), buat transaksi untuk maintenance & account creation
                $this->createPassThroughTransactionsIfNeeded($invoice);

                $invoice->needs_confirmation = false;
                $invoice->save();
            });

            $message = count($accessibleInvoices) . ' invoice berhasil disetujui dan tidak lagi memerlukan konfirmasi.';

            return redirect()->route('invoices.index', ['tab' => $tab])
                ->with('success', $message);
        }

        if ($action === 'send') {
            $sentCount = 0;
            
            $accessibleInvoices->each(function ($invoice) use (&$sentCount) {
                if (Gate::allows('send', $invoice)) {
                    $invoice->update(['status' => 'belum bayar']);
                    $sentCount++;
                }
            });

            if ($sentCount > 0) {
                $message = $sentCount . ' invoice berhasil dikirim.';
                return redirect()->route('invoices.index', ['tab' => $tab])
                    ->with('success', $message);
            }

            return redirect()->route('invoices.index', ['tab' => $tab])
                ->with('error', 'Tidak ada invoice yang dapat dikirim.');
        }

        if ($action === 'delete') {
            $deletedCount = 0;
            
            $accessibleInvoices->each(function ($invoice) use (&$deletedCount) {
                if (Gate::allows('delete', $invoice)) {
                    $invoice->delete();
                    $deletedCount++;
                }
            });

            if ($deletedCount > 0) {
                $message = $deletedCount . ' invoice berhasil dihapus.';
                return redirect()->route('invoices.index', ['tab' => $tab])
                    ->with('success', $message);
            }

            return redirect()->route('invoices.index', ['tab' => $tab])
                ->with('error', 'Tidak ada invoice yang dapat dihapus.');
        }

        return redirect()->route('invoices.index', ['tab' => $tab])
            ->with('error', 'Aksi tidak valid.');
    }

    public function checkConfirmation(): View
    {
        return view('invoices.check-confirmation');
    }

    public function searchConfirmation(Request $request): View|RedirectResponse
    {
        $request->validate([
            'invoice_number' => 'required|string|max:255',
        ], [
            'invoice_number.required' => 'Nomor invoice wajib diisi.',
        ]);

        $invoiceNumber = $request->input('invoice_number');
        
        $invoice = Invoice::where('number', $invoiceNumber)
            ->with(['customerService', 'items', 'owner', 'creator'])
            ->first();

        if (!$invoice) {
            return back()
                ->withInput()
                ->with('error', 'Invoice dengan nomor "' . $invoiceNumber . '" tidak ditemukan.');
        }

        // Tentukan status dan informasi konfirmasi
        $confirmationStatus = $this->getConfirmationStatus($invoice);

        return view('invoices.check-confirmation', [
            'invoice' => $invoice,
            'confirmationStatus' => $confirmationStatus,
            'searchedNumber' => $invoiceNumber,
        ]);
    }

    private function createPassThroughTransactionsIfNeeded(Invoice $invoice): void
    {
        $isPassThrough = in_array($invoice->type, [
            Invoice::TYPE_PASS_THROUGH_NEW,
            Invoice::TYPE_PASS_THROUGH_EXISTING
        ]);

        if (!$isPassThrough) {
            return;
        }

        $incomeCategory = \App\Models\Category::where('type', 'pemasukan')
            ->where('name', 'like', '%iklan%')
            ->first();

        if (!$incomeCategory) {
            $incomeCategory = \App\Models\Category::where('type', 'pemasukan')
                ->where('name', 'like', '%penjualan%')
                ->first();
        }

        if (!$incomeCategory) {
            return;
        }

        $invoice->loadMissing('items');
        $clientInfo = $invoice->client_name ?: $invoice->client_whatsapp ?: 'Klien';

        foreach ($invoice->items as $item) {
            if (!str_contains($item->description, 'Dana Invoices Iklan')) {
                $description = $item->description . ' - ' . $clientInfo . ' (' . $invoice->number . ')';

                $existingTransaction = \App\Models\Transaction::where('description', $description)
                    ->where('amount', $item->price * $item->quantity)
                    ->where('user_id', $invoice->user_id)
                    ->first();

                if (!$existingTransaction) {
                    \App\Models\Transaction::create([
                        'category_id' => $incomeCategory->id,
                        'date' => $invoice->issue_date ?? now(),
                        'amount' => $item->price * $item->quantity,
                        'description' => $description,
                        'user_id' => $invoice->user_id,
                    ]);
                }
            }
        }

        if (isset($this->transactionService)) {
            $this->transactionService->clearSummaryCacheForUser(\App\Models\User::find($invoice->user_id));
        }
    }

    private function getConfirmationStatus(Invoice $invoice): array
    {
        $status = [
            'label' => '',
            'color' => '',
            'icon' => '',
            'description' => '',
            'has_payment_proof' => $invoice->hasPaymentProof(),
            'payment_date' => $invoice->payment_date,
            'payment_proof_uploaded_at' => $invoice->payment_proof_uploaded_at,
        ];

        switch ($invoice->status) {
            case 'lunas':
                $status['label'] = 'Lunas';
                $status['color'] = 'green';
                $status['icon'] = '✅';
                $status['description'] = 'Pembayaran invoice ini sudah dikonfirmasi dan invoice telah lunas.';
                break;

            case 'belum lunas':
                if ($invoice->needs_confirmation) {
                    $status['label'] = 'Menunggu Konfirmasi';
                    $status['color'] = 'yellow';
                    $status['icon'] = '⏳';
                    $status['description'] = 'Bukti pembayaran sudah dikirim dan sedang menunggu verifikasi dari tim akuntansi.';
                } else {
                    $status['label'] = 'Belum Lunas';
                    $status['color'] = 'orange';
                    $status['icon'] = '💰';
                    $status['description'] = 'Invoice ini masih memiliki sisa pembayaran yang belum dilunasi.';
                }
                break;

            case 'belum bayar':
                $status['label'] = 'Belum Bayar';
                $status['color'] = 'red';
                $status['icon'] = '⚠️';
                $status['description'] = 'Invoice ini belum dibayar. Silakan lakukan pembayaran sesuai dengan instruksi pada invoice.';
                break;

            case 'draft':
                $status['label'] = 'Draft';
                $status['color'] = 'gray';
                $status['icon'] = '📝';
                $status['description'] = 'Invoice ini masih dalam tahap draft dan belum dikirimkan.';
                break;

            default:
                $status['label'] = ucfirst($invoice->status);
                $status['color'] = 'gray';
                $status['icon'] = 'ℹ️';
                $status['description'] = 'Status: ' . $invoice->status;
        }

        return $status;
    }

    public function publicCheckConfirmation(Request $request): View
    {
        /** @var \App\Models\InvoicePortalPassphrase|null $passphrase */
        $passphrase = $request->attributes->get('invoicePortalPassphrase');
        $sessionData = (array) session('invoice_portal_passphrase');

        if ($passphrase) {
            if ((int) ($sessionData['id'] ?? 0) !== $passphrase->id) {
                $sessionData = [
                    'id' => $passphrase->id,
                    'token' => Crypt::encryptString((string) $passphrase->id),
                    'access_type' => $passphrase->access_type->value,
                    'access_label' => $passphrase->access_type->label(),
                    'label' => $passphrase->label,
                    'display_label' => $passphrase->displayLabel(),
                    'verified_at' => $sessionData['verified_at'] ?? now()->toIso8601String(),
                ];

                session(['invoice_portal_passphrase' => $sessionData]);
            }
        } else {
            $sessionData = [];
        }

        $passphraseToken = $sessionData['token'] ?? null;

        return view('invoices.public-check-confirmation', [
            'passphraseSession' => $passphrase ? $sessionData : null,
            'passphraseToken' => $passphraseToken,
        ]);
    }

    public function publicSearchConfirmation(Request $request): View|RedirectResponse
    {
        $request->validate([
            'invoice_number' => 'required|string|max:255',
        ], [
            'invoice_number.required' => 'Nomor invoice wajib diisi.',
        ]);

        /** @var \App\Models\InvoicePortalPassphrase|null $passphrase */
        $passphrase = $request->attributes->get('invoicePortalPassphrase');
        $sessionData = (array) session('invoice_portal_passphrase');

        if ($passphrase) {
            if ((int) ($sessionData['id'] ?? 0) !== $passphrase->id) {
                $sessionData = [
                    'id' => $passphrase->id,
                    'token' => Crypt::encryptString((string) $passphrase->id),
                    'access_type' => $passphrase->access_type->value,
                    'access_label' => $passphrase->access_type->label(),
                    'label' => $passphrase->label,
                    'display_label' => $passphrase->displayLabel(),
                    'verified_at' => $sessionData['verified_at'] ?? now()->toIso8601String(),
                ];

                session(['invoice_portal_passphrase' => $sessionData]);
            }
        } else {
            $sessionData = [];
        }

        $passphraseToken = $sessionData['token'] ?? null;

        $invoiceNumber = $request->input('invoice_number');
        
        // Cari invoice berdasarkan nomor
        $invoice = Invoice::where('number', $invoiceNumber)
            ->with(['customerService', 'items', 'owner', 'creator'])
            ->first();

        if (!$invoice) {
            return back()
                ->withInput()
                ->with('error', 'Invoice dengan nomor "' . $invoiceNumber . '" tidak ditemukan.');
        }

        // Tentukan status dan informasi konfirmasi
        $confirmationStatus = $this->getConfirmationStatus($invoice);

        return view('invoices.public-check-confirmation', [
            'invoice' => $invoice,
            'confirmationStatus' => $confirmationStatus,
            'searchedNumber' => $invoiceNumber,
            'passphraseSession' => $passphrase ? $sessionData : null,
            'passphraseToken' => $passphraseToken,
        ]);
    }

    public function publicSearchSettlement(Request $request)
    {
        $request->validate([
            'invoice_number' => 'required|string|max:255',
        ], [
            'invoice_number.required' => 'Nomor invoice wajib diisi.',
        ]);

        $invoiceNumber = $request->input('invoice_number');
        
        $invoice = Invoice::where('number', $invoiceNumber)
            ->with(['customerService', 'items'])
            ->first();

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice dengan nomor "' . $invoiceNumber . '" tidak ditemukan.',
            ], 404);
        }

        $pdfUrl = route('invoices.public.show', ['token' => $invoice->public_token]);

        return response()->json([
            'success' => true,
            'invoice' => [
                'number' => $invoice->number,
                'client_name' => $invoice->client_name,
                'client_whatsapp' => $invoice->client_whatsapp,
                'total' => $invoice->total,
                'down_payment' => $invoice->down_payment,
                'remaining_balance' => $invoice->remaining_balance,
                'status' => $invoice->status,
                'pdf_url' => $pdfUrl,
            ],
        ]);
    }

    public function publicSearch(Request $request): View|JsonResponse
    {
        $invoices = collect();
        
        if ($request->has('search') && $request->search) {
            $request->validate([
                'search' => 'nullable|string|max:255'
            ], [
                'search.max' => 'Nomor invoice tidak boleh lebih dari 255 karakter.'
            ]);
            
            $searchTerm = trim($request->search);
            
            if (!empty($searchTerm)) {
                $query = Invoice::with('items')
                    ->where('number', 'like', '%' . $searchTerm . '%');
                
                $invoices = $query->latest()->paginate(10);
                
                $invoices->appends(['search' => $searchTerm]);
            }
        }
        
        if ($request->expectsJson() || $request->ajax()) {
            $invoicesData = $invoices->map(function ($invoice) {
                $subtotal = $invoice->items->sum(fn($item) => $item->price * $item->quantity);
                
                $statusClass = 'border-gray-500';
                $statusText = strtoupper($invoice->status);
                $statusColor = 'text-gray-400';
                if ($invoice->status == 'lunas') {
                    $statusClass = 'border-green-500';
                    $statusColor = 'text-green-400';
                } elseif ($invoice->status == 'belum lunas') {
                    $statusClass = 'border-yellow-500';
                    $statusColor = 'text-yellow-400';
                } elseif ($invoice->status == 'belum bayar') {
                    $statusClass = 'border-red-500';
                    $statusColor = 'text-red-400';
                }
                
                return [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'public_token' => $invoice->public_token,
                    'client_name' => $invoice->client_name ?? 'N/A',
                    'client_whatsapp' => $invoice->client_whatsapp,
                    'client_address' => $invoice->client_address,
                    'issue_date' => $invoice->issue_date ? $invoice->issue_date->format('d M Y') : $invoice->created_at->format('d M Y'),
                    'due_date' => $invoice->due_date ? $invoice->due_date->format('d M Y') : null,
                    'subtotal' => $subtotal,
                    'total' => $invoice->total,
                    'status' => $invoice->status,
                    'status_text' => $statusText,
                    'status_class' => $statusClass,
                    'status_color' => $statusColor,
                    'detail_url' => route('invoices.public.detail', $invoice->public_token),
                    'print_url' => route('invoices.public.print', $invoice->public_token),
                ];
            });
            
            return response()->json([
                'success' => true,
                'invoices' => $invoicesData,
                'has_pages' => $invoices->hasPages(),
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'pagination_html' => $invoices->hasPages() ? $invoices->links()->toHtml() : '',
                'search_term' => $request->search ?? '',
            ]);
        }
        
        return view('invoices.public-search', compact('invoices'));
    }

    public function publicShow(string $token): View
    {
        $invoice = Invoice::where('public_token', $token)
            ->with('items', 'customerService')
            ->firstOrFail();
        
        $companyInfo = $this->vodecoWebsiteService->getCompanyInfo();
        $paymentMethods = $this->vodecoWebsiteService->getPaymentMethods();
        
        $data = $this->invoiceViewService->prepareInvoiceData($invoice, $companyInfo, $paymentMethods);
        
        return view('invoices.page', $data);
    }

    public function publicPrint(string $token): View
    {
        $invoice = Invoice::where('public_token', $token)
            ->with('items', 'customerService')
            ->firstOrFail();
        
        $companyInfo = $this->vodecoWebsiteService->getCompanyInfo();
        $paymentMethods = $this->vodecoWebsiteService->getPaymentMethods();
        
        $data = $this->invoiceViewService->prepareInvoiceData($invoice, $companyInfo, $paymentMethods);
        
        return view('invoices.page', $data);
    }

    public function publicPaymentStatus(string $token): View
    {
        $invoice = Invoice::where('public_token', $token)
            ->with('items', 'customerService')
            ->firstOrFail();
        
        $statusInfo = $this->getConfirmationStatus($invoice);
        
        return view('invoices.payment-status', compact('invoice', 'statusInfo'));
    }

    private function generateInvoicePdfResponse(Invoice $invoice)
    {
        $invoice->loadMissing('items', 'customerService');
        
        $settings = [
            'company_name' => config('app.name', 'Company Name'),
            'company_address' => config('app.company_address', ''),
            'company_phone' => config('app.company_phone', ''),
            'company_email' => config('app.company_email', ''),
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'settings' => $settings,
        ]);

        return $pdf->setPaper('a4')->download($invoice->number . '.pdf');
    }

    /**
     * API endpoint untuk mendapatkan income categories (untuk customer-service)
     */
    public function apiCategories(): JsonResponse
    {
        $categories = Category::where('type', 'pemasukan')
            ->orderBy('name')
            ->get()
            ->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
            ]);

        return response()->json([
            'success' => true,
            'data' => $categories->values(),
        ]);
    }

    /**
     * API endpoint untuk mendapatkan pass through packages (untuk customer-service)
     */
    public function apiPassThroughPackages(): JsonResponse
    {
        $packages = $this->passThroughPackageManager->all();

        $packagesCollection = collect($packages)->map(function ($package) {
            return [
                'id' => $package->id,
                'name' => $package->name,
                'customer_type' => $package->customerType,
                'customer_label' => $package->customerLabel(),
                'daily_balance' => $package->dailyBalance,
                'duration_days' => $package->durationDays,
                'maintenance_fee' => $package->maintenanceFee,
                'account_creation_fee' => $package->accountCreationFee,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $packagesCollection->values(),
        ]);
    }

    /**
     * API endpoint untuk membuat invoice dari customer-service
     */
    public function apiStore(Request $request): JsonResponse
    {
        // Validate passphrase token if provided
        $passphraseToken = $request->input('passphrase_token');
        $passphrase = null;
        
        if ($passphraseToken) {
            try {
                $passphraseId = Crypt::decryptString($passphraseToken);
                $passphrase = InvoicePortalPassphrase::find($passphraseId);
                
                if (!$passphrase || !$passphrase->isUsable()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Passphrase tidak valid atau sudah kedaluwarsa.',
                    ], 403);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Passphrase token tidak valid.',
                ], 403);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Passphrase token wajib diisi.',
            ], 422);
        }

        $request->merge(['customer_service_name' => $request->input('customer_service_name', 'Customer Service')]);
        $request->merge(['created_by' => $request->input('created_by', auth()->id() ?? 1)]);
        
        // Add passphrase information to data
        if ($passphrase) {
            $request->merge(['passphrase_access_type' => $passphrase->access_type->value]);
            $request->merge(['created_by' => $passphrase->created_by]);
        }

        $storeRequest = new StoreInvoiceRequest();
        $storeRequest->setContainer(app());
        $storeRequest->initialize(
            $request->all(),
            $request->all(),
            $request->attributes->all(),
            $request->cookies->all(),
            $request->files->all(),
            $request->server->all(),
            $request->getContent()
        );

        if (!$storeRequest->authorize()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validator = \Illuminate\Support\Facades\Validator::make(
            $request->all(),
            $storeRequest->rules(),
            $storeRequest->messages()
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $validator->validated();
            $transactionType = $data['transaction_type'] ?? 'down_payment';
            $ownerId = $request->input('created_by', auth()->id() ?? 1);

            $invoice = null;

            if ($transactionType === 'pass_through') {
                try {
                    $context = $this->resolvePassThroughPackageContext($data);
                } catch (\RuntimeException $exception) {
                    return response()->json([
                        'success' => false,
                        'message' => $exception->getMessage(),
                        'errors' => ['pass_through_package_id' => [$exception->getMessage()]],
                    ], 422);
                }

                /** @var PassThroughPackage $package */
                $package = $context['package'];
                $quantity = max((int) ($data['pass_through_quantity'] ?? 1), 1);

                $description = trim((string) ($data['pass_through_description'] ?? ''));
                if ($description === '') {
                    $description = $context['description'];
                }

                $durationDays = (int) ($context['duration_days'] ?? 0);
                if ($durationDays <= 0) {
                    $durationDays = $package->durationDays;
                }

                $dailyBalanceUnit = max((float) ($context['daily_balance_unit'] ?? $package->dailyBalance ?? 0), 0.0);
                $maintenanceUnit = max((float) ($context['maintenance_unit'] ?? $package->maintenanceFee ?? 0), 0.0);
                $accountCreationUnit = $package->customerType === PassThroughPackage::CUSTOMER_TYPE_NEW
                    ? max((float) ($context['account_creation_unit'] ?? $package->accountCreationFee ?? 0), 0.0)
                    : 0.0;

                $dailyBalanceUnit = round($dailyBalanceUnit, 2);
                $adBudgetUnit = round($dailyBalanceUnit * $durationDays, 2);
                $maintenanceUnit = round($maintenanceUnit, 2);
                $accountCreationUnit = round($accountCreationUnit, 2);

                $adBudgetTotal = round($adBudgetUnit * $quantity, 2);
                $maintenanceTotal = round($maintenanceUnit * $quantity, 2);
                $accountCreationTotal = round($accountCreationUnit * $quantity, 2);
                $total = round($adBudgetTotal + $maintenanceTotal + $accountCreationTotal, 2);

                $customerServiceId = \App\Models\CustomerService::query()
                    ->where('user_id', $ownerId)
                    ->value('id');

                $invoice = $this->passThroughInvoiceCreator->create(
                    ownerId: $ownerId,
                    createdBy: $data['created_by'] ?? $ownerId,
                    customerServiceId: $customerServiceId,
                    customerServiceName: $data['customer_service_name'] ?? 'Customer Service',
                    clientName: $data['client_name'],
                    clientWhatsapp: $data['client_whatsapp'],
                    clientAddress: $data['client_address'] ?? null,
                    package: $package,
                    quantity: $quantity,
                    description: $description,
                    dailyBalanceUnit: $dailyBalanceUnit,
                    durationDays: $durationDays,
                    adBudgetUnit: $adBudgetUnit,
                    maintenanceUnit: $maintenanceUnit,
                    accountCreationUnit: $accountCreationUnit,
                );
            } elseif ($transactionType === 'settlement') {
                $referenceInvoice = Invoice::where('number', $data['settlement_invoice_number'])->firstOrFail();
                $invoice = $this->persistSettlementInvoice($data, $referenceInvoice);
            } else {
                $invoice = $this->persistInvoice($data, $ownerId);
            }

            if ($invoice) {
                try {
                    $this->regenerateInvoicePdf($invoice);
                } catch (Throwable $exception) {
                    \Log::error('Failed to generate PDF for invoice after creation', [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->number,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Invoice berhasil dibuat',
                'data' => [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'total' => $invoice->total,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error creating invoice via API', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat invoice: ' . $e->getMessage(),
                'errors' => [],
            ], 500);
        }
    }
}
