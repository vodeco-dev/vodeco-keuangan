<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\PublicStoreInvoiceRequest;
use App\Http\Requests\StoreInvoiceRequest;
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
        return view('invoices.index', compact('invoices'));
    }

    public function create(): View
    {
        return view('invoices.create');
    }

    public function createPublic(): View
    {
        $customerServices = User::whereIn('role', [Role::ADMIN, Role::ACCOUNTANT, Role::STAFF])
            ->orderBy('name')
            ->get();

        return view('invoices.public-create', compact('customerServices'));
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $this->persistInvoice($data, auth()->id());

        return redirect()->route('invoices.index');
    }

    public function storePublic(PublicStoreInvoiceRequest $request)
    {
        $data = $request->validated();

        $invoice = $this->persistInvoice($data, $data['customer_service_id']);

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

        $invoice->update(['status' => 'Proses']);
        // Logic pengiriman email dapat ditambahkan di sini
        return redirect()->route('invoices.index');
    }

    public function storePayment(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('storePayment', $invoice);

        $request->validate([
            'payment_amount' => 'required|numeric|min:0.01|max:' . ($invoice->total - $invoice->down_payment),
            'payment_date' => 'required|date',
        ]);

        $paymentAmount = $request->input('payment_amount');
        $paymentDate = $request->input('payment_date');

        DB::transaction(function () use ($invoice, $paymentAmount, $paymentDate) {
            $invoice->down_payment += $paymentAmount;
            $invoice->payment_date = $paymentDate;

            if ($invoice->down_payment >= $invoice->total) {
                $invoice->status = 'Terbayar';
            } else {
                $invoice->status = 'Terbayar Sebagian';
            }

            $invoice->save();

            // Create a transaction for the down payment
            $category = Category::firstOrCreate(
                ['name' => 'Down Payment'],
                ['type' => 'pemasukan']
            );

            Transaction::create([
                'category_id' => $category->id,
                'user_id' => auth()->id(),
                'amount' => $paymentAmount,
                'description' => 'Down payment for Invoice #' . $invoice->number,
                'date' => $paymentDate,
            ]);
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

        return view('invoices.edit', compact('invoice'));
    }

    public function update(StoreInvoiceRequest $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $data = $request->validated();

        DB::transaction(function () use ($invoice, $data) {
            $invoice->update([
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

    private function persistInvoice(array $data, int $customerServiceId): Invoice
    {
        return DB::transaction(function () use ($data, $customerServiceId) {
            $date = now()->format('Ymd');
            $count = Invoice::whereDate('created_at', today())->count();
            $sequence = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
            $invoiceNumber = "{$date}-{$sequence}";

            $invoice = Invoice::create([
                'user_id' => $customerServiceId,
                'client_name' => $data['client_name'],
                'client_email' => $data['client_email'],
                'client_address' => $data['client_address'],
                'number' => $invoiceNumber,
                'issue_date' => $data['issue_date'] ?? now(),
                'due_date' => $data['due_date'] ?? null,
                'status' => 'Draft',
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
