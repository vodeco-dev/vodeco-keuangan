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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use App\Models\User;

class InvoiceController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Invoice::class, 'invoice');
    }
    public function index(): View
    {
        if (auth()->user()->role === Role::ADMIN) {
            $invoices = Invoice::with('customerService')->latest()->paginate();
        } else {
            $invoices = Invoice::with('customerService')
                ->where('user_id', auth()->id())
                ->latest()
                ->paginate();
        }

        $incomeCategories = Category::where('type', 'pemasukan')
            ->orderBy('name')
            ->get();

        return view('invoices.index', compact('invoices', 'incomeCategories'));
    }

    public function create(): View
    {
        $customerServices = CustomerService::orderBy('name')->get();

        return view('invoices.create', compact('customerServices'));
    }

    public function createPublic(): View
    {
        $customerServices = CustomerService::orderBy('name')->get();

        return view('invoices.public-create', compact('customerServices'));
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
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

        $ownerId = $customerService?->user_id ?? auth()->id();

        $this->persistInvoice($data, $customerService, $ownerId);

        return redirect()->route('invoices.index');
    }

    public function storePublic(PublicStoreInvoiceRequest $request)
    {
        $data = $request->validated();

        $customerService = CustomerService::where('name', $data['customer_service_name'])->first();

        if (! $customerService) {
            return back()
                ->withErrors(['customer_service_name' => 'Customer service yang dimaksud tidak ditemukan.'])
                ->withInput();
        }

        $ownerId = $customerService->user_id
            ?? User::where('role', Role::ADMIN)->orderBy('id')->value('id');

        if (! $ownerId) {
            return back()
                ->withErrors(['customer_service_name' => 'Customer service belum dapat digunakan saat ini.'])
                ->withInput();
        }

        $invoice = $this->persistInvoice($data, $customerService, $ownerId);

        $settings = Setting::pluck('value', 'key')->all();

        return Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'settings' => $settings,
        ])
            ->setPaper('a4')
            ->download($invoice->number . '.pdf');
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
            $invoice->down_payment = round($invoice->down_payment + $paymentAmount, 2);
            $invoice->payment_date = $paymentDate;

            $downPaymentTotal = round((float) $invoice->down_payment, 2);
            $totalAmount = round((float) $invoice->total, 2);

            if ($downPaymentTotal >= $totalAmount) {
                $invoice->status = 'lunas';
            } elseif ($downPaymentTotal > 0) {
                $invoice->status = 'belum lunas';
            } else {
                $invoice->status = 'belum bayar';
            }

            $invoice->save();

            if ($willBePaidOff) {
                Transaction::create([
                    'category_id' => $categoryId,
                    'user_id' => auth()->id(),
                    'amount' => $paymentAmount,
                    'description' => 'Pembayaran lunas Invoice #' . $invoice->number,
                    'date' => $paymentDate,
                ]);
            } else {
                $category = Category::firstOrCreate(
                    ['name' => 'Down Payment'],
                    ['type' => 'pemasukan']
                );

                Transaction::create([
                    'category_id' => $category->id,
                    'user_id' => auth()->id(),
                    'amount' => $paymentAmount,
                    'description' => 'Down payment untuk Invoice #' . $invoice->number,
                    'date' => $paymentDate,
                ]);
            }
        });

        return redirect()->route('invoices.index')->with('success', 'Payment recorded successfully.');
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

        return view('invoices.edit', compact('invoice', 'customerServices'));
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
            $invoice->update([
                'user_id' => $ownerId,
                'customer_service_id' => $customerService?->id,
                'customer_service_name' => $customerService?->name ?? null,
                'client_name' => $data['client_name'],
                'client_email' => $data['client_email'],
                'client_address' => $data['client_address'],
                'issue_date' => $data['issue_date'] ?? now(),
                'due_date' => $data['due_date'] ?? null,
                'total' => collect($data['items'])->sum(fn ($item) => $item['quantity'] * $item['price']),
            ]);

            $invoice->items()->delete();

            foreach ($data['items'] as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }
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
            $date = now()->format('Ymd');
            $count = Invoice::whereDate('created_at', today())->count();
            $sequence = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
            $invoiceNumber = "{$date}-{$sequence}";

            $invoice = Invoice::create([
                'user_id' => $ownerId ?? auth()->id(),
                'customer_service_id' => $customerService?->id,
                'customer_service_name' => $customerService?->name ?? ($data['customer_service_name'] ?? null),
                'client_name' => $data['client_name'],
                'client_email' => $data['client_email'],
                'client_address' => $data['client_address'],
                'number' => $invoiceNumber,
                'issue_date' => $data['issue_date'] ?? now(),
                'due_date' => $data['due_date'] ?? null,
                'status' => 'belum bayar',
                'total' => collect($data['items'])->sum(fn ($item) => $item['quantity'] * $item['price']),
            ]);

            foreach ($data['items'] as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }

            return $invoice->load('items', 'customerService');
        });
    }
}
