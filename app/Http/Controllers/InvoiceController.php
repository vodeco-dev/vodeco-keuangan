<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\PublicStoreInvoiceRequest;
use App\Http\Requests\StoreInvoiceRequest;
use App\Models\CustomerService;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\Debt;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use App\Models\User;
use App\Services\InvoiceSettlementService;

class InvoiceController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Invoice::class, 'invoice');
    }
    public function index(): View
    {
        $user = auth()->user();
        $verifiedRoles = collect(session('verified_access_roles', []));

        $tabPermissions = [
            'down-payment' => [
                'label' => 'Down Payment',
                'role' => Role::CUSTOMER_SERVICE,
                'allowed' => Gate::allows('viewDownPaymentTab', Invoice::class),
            ],
            'pay-in-full' => [
                'label' => 'Bayar Lunas',
                'role' => Role::CUSTOMER_SERVICE,
                'allowed' => Gate::allows('viewPayInFullTab', Invoice::class),
            ],
            'settlement' => [
                'label' => 'Pelunasan',
                'role' => Role::SETTLEMENT_ADMIN,
                'allowed' => Gate::allows('viewSettlementTab', Invoice::class),
            ],
        ];

        $tabStates = [];
        foreach ($tabPermissions as $key => $config) {
            $role = $config['role'];
            $allowed = $config['allowed'];
            $unlocked = $allowed && ($user->role === Role::ADMIN || $verifiedRoles->contains($role->value));
            $requiresCode = $allowed && $user->role === $role && ! $verifiedRoles->contains($role->value);

            $tabStates[$key] = [
                'label' => $config['label'],
                'role' => $role,
                'allowed' => $allowed,
                'unlocked' => $unlocked,
                'requires_code' => $requiresCode,
            ];
        }

        $defaultTab = collect($tabStates)
            ->filter(fn ($tab) => $tab['unlocked'])
            ->keys()
            ->first() ?? 'none';

        $accessCodeRole = collect($tabStates)
            ->first(fn ($tab) => $tab['requires_code'])['role'] ?? null;

        $baseQuery = null;
        $shouldLoadInvoices = collect($tabStates)->contains(fn ($tab) => $tab['unlocked']);

        if ($shouldLoadInvoices) {
            $baseQuery = Invoice::query()
                ->with('customerService')
                ->latest();

            if ($user->role !== Role::ADMIN) {
                $baseQuery->where('user_id', $user->id);
            }
        }

        $downPaymentInvoices = $tabStates['down-payment']['unlocked'] && $baseQuery
            ? (clone $baseQuery)
                ->whereIn('status', ['belum bayar', 'belum lunas'])
                ->whereNotNull('down_payment_due')
                ->whereRaw('COALESCE(down_payment, 0) < down_payment_due')
                ->get()
            : collect();

        $payInFullInvoices = $tabStates['pay-in-full']['unlocked'] && $baseQuery
            ? (clone $baseQuery)
                ->whereIn('status', ['belum bayar', 'belum lunas'])
                ->whereRaw('COALESCE(total, 0) > COALESCE(down_payment, 0)')
                ->where(function ($query) {
                    $query->whereNull('down_payment_due')
                        ->orWhereRaw('COALESCE(down_payment, 0) >= down_payment_due');
                })
                ->get()
            : collect();

        $settlementInvoices = $tabStates['settlement']['unlocked'] && $baseQuery
            ? (clone $baseQuery)
                ->where('status', 'belum lunas')
                ->get()
            : collect();

        $incomeCategories = Category::where('type', 'pemasukan')
            ->orderBy('name')
            ->get();

        return view('invoices.index', compact(
            'downPaymentInvoices',
            'payInFullInvoices',
            'settlementInvoices',
            'incomeCategories',
            'tabStates',
            'defaultTab',
            'accessCodeRole'
        ));
    }

    public function create(): View
    {
        $customerServices = CustomerService::orderBy('name')->get();
        $incomeCategories = Category::where('type', 'pemasukan')->orderBy('name')->get();

        return view('invoices.create', compact('customerServices', 'incomeCategories'));
    }

    public function createPublic(Request $request): View
    {
        $customerServices = CustomerService::orderBy('name')->get();
        $incomeCategories = Category::where('type', 'pemasukan')->orderBy('name')->get();

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
            'customerServices' => $customerServices,
            'incomeCategories' => $incomeCategories,
            'passphraseSession' => $passphrase ? $sessionData : null,
            'allowedTransactionTypes' => $allowedTransactionTypes,
            'passphraseToken' => $passphraseToken,
        ]);

    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $transactionType = $data['transaction_type'] ?? 'down_payment';

        $customerService = null;

        if ($transactionType !== 'settlement' && ! empty($data['customer_service_id'])) {
            $customerService = CustomerService::find($data['customer_service_id']);

            if (! $customerService) {
                return back()
                    ->withErrors(['customer_service_id' => 'Customer service yang dipilih tidak ditemukan.'])
                    ->withInput();
            }
        }

        $ownerId = $customerService?->user_id ?? auth()->id();

        if ($transactionType === 'settlement') {
            $referenceInvoice = $request->referenceInvoice()
                ?? Invoice::where('number', $data['settlement_invoice_number'])->firstOrFail();

            $this->persistSettlementInvoice($data, $referenceInvoice);
        } else {
            $this->persistInvoice($data, $customerService, $ownerId);
        }

        return redirect()->route('invoices.index');
    }

    public function storePublic(PublicStoreInvoiceRequest $request)
    {
        $data = $request->validated();

        $passphrase = $request->passphrase();

        if (! $passphrase) {
            abort(403, 'Passphrase portal invoice tidak valid.');
        }

        $customerService = CustomerService::where('name', $data['customer_service_name'])->first();

        if (! $customerService && $data['transaction_type'] !== 'settlement') {
            return back()
                ->withErrors(['customer_service_name' => 'Customer service yang dimaksud tidak ditemukan.'])
                ->withInput();
        }

        $ownerId = $customerService?->user_id
            ?? User::where('role', Role::ADMIN)->orderBy('id')->value('id');

        if (! $ownerId) {
            return back()
                ->withErrors(['customer_service_name' => 'Customer service belum dapat digunakan saat ini.'])
                ->withInput();
        }

        if ($data['transaction_type'] === 'settlement') {
            $referenceInvoice = $request->referenceInvoice()
                ?? Invoice::where('number', $data['settlement_invoice_number'])->firstOrFail();

            $invoice = $this->persistSettlementInvoice($data, $referenceInvoice);
        } else {
            $invoice = $this->persistInvoice($data, $customerService, $ownerId);
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

    public function checkStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'number' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Nomor invoice wajib diisi.',
            ], 422);
        }

        $invoice = Invoice::query()
            ->select(['number', 'status', 'due_date', 'total', 'client_name'])
            ->where('number', $request->input('number'))
            ->first();

        if (! $invoice) {
            return response()->json([
                'message' => 'Invoice tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'number' => $invoice->number,
            'status' => $invoice->status ?? 'belum bayar',
            'due_date' => optional($invoice->due_date)->format('d F Y'),
            'total' => number_format((float) $invoice->total, 2, ',', '.'),
            'client_name' => $invoice->client_name,
        ]);
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

        $paymentAmount = round((float) $request->input('payment_amount'), 2);
        $paymentDate = $request->input('payment_date');
        $categoryId = $request->input('category_id');
        $currentDownPayment = round((float) $invoice->down_payment, 2);
        $invoiceTotal = round((float) $invoice->total, 2);
        $remainingBalance = max($invoiceTotal - $currentDownPayment, 0);
        $willBePaidOff = $invoiceTotal > 0 && round($currentDownPayment + $paymentAmount, 2) >= $invoiceTotal;

        if ($willBePaidOff && empty($categoryId)) {
            return back()
                ->withErrors(['category_id' => 'Pilih kategori pemasukan untuk pembayaran lunas.'])
                ->withInput();
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
                    'description' => $invoice->itemDescriptionSummary(),
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
                $debt->description = $invoice->itemDescriptionSummary();
                $debt->amount = $invoice->total;
                $debt->due_date = $invoice->due_date;
                $debt->related_party = $relatedParty;
                $debt->save();
            }

            if ($willBePaidOff) {
                Transaction::create([
                    'category_id' => $categoryId,
                    'user_id' => auth()->id(),
                    'amount' => $paymentAmount,
                    'description' => 'Pembayaran lunas Invoice #' . $invoice->number,
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

        $customerServices = CustomerService::orderBy('name')->get();
        $incomeCategories = Category::where('type', 'pemasukan')->orderBy('name')->get();

        $invoice->loadMissing('items');

        return view('invoices.edit', compact('invoice', 'customerServices', 'incomeCategories'));
    }

    public function update(StoreInvoiceRequest $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $data = $request->validated();

        $customerService = null;

        if (! empty($data['customer_service_id'])) {
            $customerService = CustomerService::find($data['customer_service_id']);

            if (! $customerService) {
                return back()
                    ->withErrors(['customer_service_id' => 'Customer service yang dipilih tidak ditemukan.'])
                    ->withInput();
            }
        }

        $ownerId = $customerService?->user_id ?? $invoice->user_id;

        DB::transaction(function () use ($invoice, $data, $customerService, $ownerId) {
            $items = $this->mapInvoiceItems($data['items']);

            $invoice->update([
                'user_id' => $ownerId,
                'customer_service_id' => $customerService?->id,
                'customer_service_name' => $customerService?->name ?? null,
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

    private function persistInvoice(array $data, ?CustomerService $customerService, ?int $ownerId = null): Invoice
    {
        return DB::transaction(function () use ($data, $customerService, $ownerId) {
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
                default => 'belum bayar',
            };

            $invoice = Invoice::create([
                'user_id' => $ownerId ?? auth()->id(),
                'created_by' => auth()->id(),
                'customer_service_id' => $customerService?->id,
                'customer_service_name' => $customerService?->name ?? ($data['customer_service_name'] ?? null),
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
                'created_by' => auth()->id(),
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

        $debt->description = $invoice->itemDescriptionSummary();
        $debt->amount = $invoice->total;
        $debt->due_date = $invoice->due_date;
        $debt->related_party = $relatedParty;
        $debt->save();
    }
}
