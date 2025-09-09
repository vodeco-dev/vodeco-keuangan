<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Transaction;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\Setting;
use Spatie\LaravelPdf\Facades\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Invoice::class, 'invoice');
    }
    public function index(): View
    {
        $invoices = Invoice::latest()->paginate();
        return view('invoices.index', compact('invoices'));
    }

    public function create(): View
    {
        return view('invoices.create');
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            $invoice = Invoice::create([
                'client_name' => $data['client_name'],
                'client_email' => $data['client_email'],
                'client_address' => $data['client_address'],
                'number' => $data['number'],
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
        });

        return redirect()->route('invoices.index');
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

        // Ambil pengaturan bisnis dari database
        $settings = Setting::pluck('value', 'key')->all();

        // Buat PDF menggunakan Spatie/laravel-pdf
        $pdf = Pdf::view('invoices.pdf', [
            'invoice' => $invoice,
            'settings' => $settings,
        ])
            ->format('a4')
            ->name($invoice->number . '.pdf');

        return $pdf;
    }

    public function showPublic(string $token)
    {
        $invoice = Invoice::where('public_token', $token)->firstOrFail();

        // Ambil pengaturan bisnis dari database
        $settings = Setting::pluck('value', 'key')->all();

        // Buat PDF menggunakan Spatie/laravel-pdf
        $pdf = Pdf::view('invoices.pdf', [
            'invoice' => $invoice,
            'settings' => $settings,
        ])
            ->format('a4')
            ->name($invoice->number . '.pdf');

        return $pdf;
    }
}
