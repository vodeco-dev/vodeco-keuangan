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
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use App\Models\User;
use App\Services\CategoryService;
use App\Services\InvoiceSettlementService;
use App\Services\PassThroughInvoiceCreator;
use App\Services\PassThroughPackageManager;
use App\Support\PassThroughPackage;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class InvoiceController extends Controller
{
    public function __construct(
        private PassThroughInvoiceCreator $passThroughInvoiceCreator,
        private PassThroughPackageManager $passThroughPackageManager
    )
    {
        $this->authorizeResource(Invoice::class, 'invoice');
    }
    public function index(Request $request): View
    {
        $user = auth()->user();
        $verifiedRoles = collect(session('verified_access_roles', []));

        $tabPermissions = [
            'down-payment' => [
                'label' => 'Down Payment',
                'role' => null,
                'allowed' => in_array($user->role, [Role::ADMIN, Role::STAFF, Role::ACCOUNTANT], true),
            ],
            'pay-in-full' => [
                'label' => 'Bayar Lunas',
                'role' => null,
                'allowed' => in_array($user->role, [Role::ADMIN, Role::STAFF, Role::ACCOUNTANT], true),
            ],
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
                ->with('customerService')
                ->latest('issue_date');

            if ($user->role !== Role::ADMIN && $user->role !== Role::ACCOUNTANT) {
                $baseQuery->where('user_id', $user->id);
            }
        }

        $needsConfirmationInvoicesQuery = $tabStates['needs-confirmation']['unlocked'] && $baseQuery
            ? (clone $baseQuery)->where('needs_confirmation', true)
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

        \Illuminate\Support\Facades\Storage::put('debug_packages.json', json_encode($passThroughPackages));

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
                $this->passThroughInvoiceCreator->create(
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

            $this->persistSettlementInvoice($data, $referenceInvoice);
        } else {
            $data['customer_service_name'] = auth()->user()?->name;
            $this->persistInvoice($data, $ownerId);
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

        $settings = Setting::pluck('value', 'key')->all();

        $passphrase->markAsUsed($request->ip(), $request->userAgent(), 'submission');

        return Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'settings' => $settings,
        ])
            ->setPaper('a4')
            ->download($invoice->number . '.pdf');
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
        // Logic pengiriman email dapat ditambahkan di sini
        return redirect()->route('invoices.index');
    }

    public function storePayment(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('storePayment', $invoice);

        $request->validate([
            'payment_amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $invoice->loadMissing('items');

        $paymentAmount = round((float) $request->input('payment_amount'), 2);
        $paymentDate = $request->input('payment_date');
        $categoryId = $request->input('category_id');
        $currentDownPayment = round((float) $invoice->down_payment, 2);
        $invoiceTotal = round((float) $invoice->total, 2);
        $remainingBalance = max($invoiceTotal - $currentDownPayment, 0);
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

        DB::transaction(function () use ($invoice, $paymentAmount, $paymentDate, $categoryId, $willBePaidOff) {
            $invoice->loadMissing('items', 'debt.payments');

            $relatedParty = $invoice->client_name
                ?: ($invoice->client_whatsapp ?: 'Klien Invoice #' . $invoice->number);
            $paymentNotes = $willBePaidOff
                ? 'Pelunasan invoice #' . $invoice->number
                : 'Pembayaran down payment invoice #' . $invoice->number;

            $debt = Debt::updateOrCreate(
                ['invoice_id' => $invoice->id],
                [
                    'description' => $invoice->transactionDescription(),
                    'related_party' => $relatedParty,
                    'type' => Debt::TYPE_DOWN_PAYMENT,
                    'amount' => $invoice->total,
                    'due_date' => $invoice->due_date,
                    'status' => $willBePaidOff ? Debt::STATUS_LUNAS : Debt::STATUS_BELUM_LUNAS,
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

            if ($debt) {
                $debt->payments()->create([
                    'amount' => $paymentAmount,
                    'payment_date' => $paymentDate,
                    'notes' => $paymentNotes,
                ]);

                $debt->load('payments');
            }

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

            $invoice->save();

            if ($debt) {
                $debt->status = $invoice->status === 'lunas'
                    ? Debt::STATUS_LUNAS
                    : Debt::STATUS_BELUM_LUNAS;
                $debt->description = $invoice->transactionDescription();
                $debt->amount = $invoice->total;
                $debt->due_date = $invoice->due_date;
                $debt->related_party = $relatedParty;
                $debt->save();
            }

            if ($willBePaidOff) {
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

    public function pdf(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->loadMissing('items', 'customerService');

        // Ambil pengaturan bisnis dari database
        $settings = Setting::pluck('value', 'key')->all();

        if (app()->runningUnitTests()) {
            return response()
                ->view('invoices.pdf', [
                    'invoice' => $invoice,
                    'settings' => $settings,
                ])
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="'.$invoice->number.'.pdf"');
        }

        return Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'settings' => $settings,
        ])
            ->setPaper('a4')
            ->download($invoice->number . '.pdf');
    }

    public function showPublic(string $token)
    {
        $invoice = Invoice::where('public_token', $token)->firstOrFail();

        $invoice->loadMissing('items', 'customerService');

        // Ambil pengaturan bisnis dari database
        $settings = Setting::pluck('value', 'key')->all();

        if (app()->runningUnitTests()) {
            return response()
                ->view('invoices.pdf', [
                    'invoice' => $invoice,
                    'settings' => $settings,
                ])
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="'.$invoice->number.'.pdf"');
        }

        return Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'settings' => $settings,
        ])
            ->setPaper('a4')
            ->download($invoice->number . '.pdf');
    }

    public function show(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

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

        return redirect()->route('invoices.index');
    }

    public function destroy(Invoice $invoice)
    {
        $this->authorize('delete', $invoice);

        $invoice->delete();

        return redirect()->route('invoices.index');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{package: PassThroughPackage, description: string, duration_days: int, daily_balance_unit: float, maintenance_unit: float, account_creation_unit: float}
     */
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

            $status = match ($transactionType) {
                'down_payment' => 'belum lunas',
                'full_payment' => 'lunas',
                default => 'belum bayar',
            };

            $downPayment = $transactionType === 'full_payment' ? $total : 0;
            $paymentDate = $transactionType === 'full_payment' ? now() : null;

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
                'down_payment' => $downPayment,
                'down_payment_due' => $downPaymentDue,
                'payment_date' => $paymentDate,
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

        return "{$date}-{$sequence}";
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
}
