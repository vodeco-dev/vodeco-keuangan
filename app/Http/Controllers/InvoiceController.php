<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\Debt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use App\Services\InvoiceSettlementService;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Illuminate\Validation\Rule;

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
                'role' => null,
                'allowed' => Gate::allows('viewDownPaymentTab', Invoice::class),
            ],
            'pay-in-full' => [
                'label' => 'Bayar Lunas',
                'role' => null,
                'allowed' => Gate::allows('viewPayInFullTab', Invoice::class),
            ],
            'settlement' => [
                'label' => 'Pelunasan',
                'role' => null,
                'allowed' => Gate::allows('viewSettlementTab', Invoice::class),
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

            if ($user->role !== Role::ADMIN && $user->role !== Role::ACCOUNTANT) {
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
                ->whereIn('status', ['belum bayar', 'belum lunas'])
                ->where('type', '!=', Invoice::TYPE_SETTLEMENT)
                ->whereNotNull('settlement_token')
                ->where(function ($query) {
                    $query->whereNull('settlement_token_expires_at')
                        ->orWhere('settlement_token_expires_at', '>', now());
                })
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

    public function update(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $items = collect($request->input('items', []))->map(function ($item) {
            if (isset($item['price'])) {
                $normalizedPrice = preg_replace('/[^\d,.-]/', '', (string) $item['price']);
                $normalizedPrice = str_replace('.', '', $normalizedPrice);
                $normalizedPrice = str_replace(',', '.', $normalizedPrice);
                $item['price'] = $normalizedPrice === '' ? null : $normalizedPrice;
            }

            if (isset($item['quantity']) && ! is_numeric($item['quantity'])) {
                $digits = preg_replace('/\D/', '', (string) $item['quantity']);
                $item['quantity'] = $digits !== '' ? (int) $digits : null;
            }

            return $item;
        })->toArray();

        $whatsapp = $request->input('client_whatsapp');
        if (is_string($whatsapp)) {
            $whatsapp = preg_replace('/[^\d+]/', '', $whatsapp);
        }

        $request->merge([
            'items' => $items,
            'client_whatsapp' => $whatsapp,
            'down_payment_due' => $this->sanitizeCurrencyValue($request->input('down_payment_due')),
        ]);

        $data = $request->validate([
            'client_name' => ['required', 'string', 'max:255'],
            'client_whatsapp' => ['nullable', 'string', 'max:32'],
            'client_address' => ['nullable', 'string'],
            'issue_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'down_payment_due' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric'],
            'items.*.category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where('type', 'pemasukan'),
            ],
        ]);

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

    private function sanitizeCurrencyValue($value): ?string
    {
        if (! isset($value)) {
            return null;
        }

        $normalized = preg_replace('/[^\d,.-]/', '', (string) $value);
        $normalized = str_replace('.', '', $normalized);
        $normalized = str_replace(',', '.', $normalized);

        return $normalized === '' ? null : $normalized;
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
