<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
{
    protected ?Invoice $referenceInvoice = null;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $transactionTypes = ['down_payment', 'full_payment', 'settlement'];

        return [
            'transaction_type' => ['required', Rule::in($transactionTypes)],
            'customer_service_id' => ['nullable', 'exists:customer_services,id'],
            'client_name' => ['required_unless:transaction_type,settlement', 'nullable', 'string', 'max:255'],
            'client_whatsapp' => ['required_unless:transaction_type,settlement', 'nullable', 'string', 'max:32'],
            'client_address' => ['required_unless:transaction_type,settlement', 'nullable', 'string'],
            'issue_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'down_payment_due' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required_unless:transaction_type,settlement', 'array', 'min:1'],
            'items.*.description' => ['required_unless:transaction_type,settlement', 'string'],
            'items.*.quantity' => ['required_unless:transaction_type,settlement', 'integer', 'min:1'],
            'items.*.price' => ['required_unless:transaction_type,settlement', 'numeric'],
            'items.*.category_id' => [
                'required_unless:transaction_type,settlement',
                'integer',
                Rule::exists('categories', 'id')->where('type', 'pemasukan'),
            ],
            'settlement_invoice_number' => ['required_if:transaction_type,settlement', 'nullable', 'string'],
            'settlement_remaining_balance' => ['required_if:transaction_type,settlement', 'nullable', 'numeric', 'min:0'],
            'settlement_payment_status' => [
                'required_if:transaction_type,settlement',
                'nullable',
                Rule::in(['paid_full', 'paid_partial']),
            ],
            'settlement_paid_amount' => ['required_if:transaction_type,settlement', 'nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'transaction_type.required' => 'Jenis transaksi wajib dipilih.',
            'transaction_type.in' => 'Jenis transaksi tidak valid.',
            'client_name.required_unless' => 'Nama klien wajib diisi untuk transaksi selain pelunasan.',
            'client_whatsapp.required_unless' => 'Nomor WhatsApp klien wajib diisi untuk transaksi selain pelunasan.',
            'client_whatsapp.max' => 'Nomor WhatsApp klien terlalu panjang.',
            'client_address.required_unless' => 'Alamat klien wajib diisi untuk transaksi selain pelunasan.',
            'issue_date.date' => 'Format tanggal terbit tidak valid.',
            'due_date.date' => 'Format tanggal jatuh tempo tidak valid.',
            'down_payment_due.numeric' => 'Rencana down payment harus berupa angka.',
            'down_payment_due.min' => 'Rencana down payment minimal 0.',
            'customer_service_id.exists' => 'Customer service yang dipilih tidak valid.',
            'items.required_unless' => 'Minimal satu item wajib ditambahkan untuk transaksi ini.',
            'items.*.description.required_unless' => 'Deskripsi item wajib diisi.',
            'items.*.description.string' => 'Deskripsi item harus berupa teks.',
            'items.*.quantity.required_unless' => 'Kuantitas item wajib diisi.',
            'items.*.quantity.integer' => 'Kuantitas item harus berupa angka.',
            'items.*.quantity.min' => 'Kuantitas item minimal 1.',
            'items.*.price.required_unless' => 'Harga item wajib diisi.',
            'items.*.price.numeric' => 'Harga item harus berupa angka.',
            'items.*.category_id.required_unless' => 'Kategori item wajib dipilih.',
            'items.*.category_id.exists' => 'Kategori item tidak valid.',
            'settlement_invoice_number.required_if' => 'Nomor invoice referensi wajib diisi untuk pelunasan.',
            'settlement_remaining_balance.required_if' => 'Sisa tagihan wajib diisi untuk pelunasan.',
            'settlement_remaining_balance.numeric' => 'Sisa tagihan harus berupa angka.',
            'settlement_payment_status.required_if' => 'Pilih status pembayaran pelunasan.',
            'settlement_payment_status.in' => 'Status pembayaran pelunasan tidak dikenal.',
            'settlement_paid_amount.required_if' => 'Nominal yang dibayarkan wajib diisi.',
            'settlement_paid_amount.numeric' => 'Nominal yang dibayarkan harus berupa angka.',
            'settlement_paid_amount.min' => 'Nominal yang dibayarkan minimal 0.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $transactionType = $this->input('transaction_type', 'down_payment');

        $items = collect($this->input('items', []))->map(function ($item) {
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
        });

        $whatsapp = $this->input('client_whatsapp');
        if (is_string($whatsapp)) {
            $whatsapp = preg_replace('/[^\d+]/', '', $whatsapp);
        }

        $downPaymentDue = $this->input('down_payment_due');
        if (isset($downPaymentDue)) {
            $normalizedDownPaymentDue = preg_replace('/[^\d,.-]/', '', (string) $downPaymentDue);
            $normalizedDownPaymentDue = str_replace('.', '', $normalizedDownPaymentDue);
            $normalizedDownPaymentDue = str_replace(',', '.', $normalizedDownPaymentDue);
            $downPaymentDue = $normalizedDownPaymentDue === '' ? null : $normalizedDownPaymentDue;
        }

        $settlementRemaining = $this->input('settlement_remaining_balance');
        if (isset($settlementRemaining)) {
            $normalized = preg_replace('/[^\d,.-]/', '', (string) $settlementRemaining);
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
            $settlementRemaining = $normalized === '' ? null : $normalized;
        }

        $settlementPaidAmount = $this->input('settlement_paid_amount');
        if (isset($settlementPaidAmount)) {
            $normalized = preg_replace('/[^\d,.-]/', '', (string) $settlementPaidAmount);
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
            $settlementPaidAmount = $normalized === '' ? null : $normalized;
        }

        $this->merge([
            'transaction_type' => $transactionType,
            'items' => $items->toArray(),
            'client_whatsapp' => $whatsapp,
            'down_payment_due' => $downPaymentDue,
            'settlement_remaining_balance' => $settlementRemaining,
            'settlement_paid_amount' => $settlementPaidAmount,
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('transaction_type') !== 'settlement') {
                return;
            }

            $number = $this->input('settlement_invoice_number');
            if (! $number) {
                return;
            }

            $invoice = Invoice::where('number', $number)->first();

            if (! $invoice) {
                $validator->errors()->add('settlement_invoice_number', 'Invoice referensi tidak ditemukan.');
                return;
            }

            if ($invoice->type === Invoice::TYPE_SETTLEMENT) {
                $validator->errors()->add('settlement_invoice_number', 'Invoice pelunasan tidak dapat dijadikan referensi.');
                return;
            }

            $currentDownPayment = round((float) $invoice->down_payment, 2);
            $invoiceTotal = round((float) $invoice->total, 2);
            $remainingBalance = max($invoiceTotal - $currentDownPayment, 0);

            if ($remainingBalance <= 0) {
                $validator->errors()->add('settlement_invoice_number', 'Invoice referensi sudah lunas.');
                return;
            }

            $inputRemaining = round((float) $this->input('settlement_remaining_balance', 0), 2);
            if (abs($remainingBalance - $inputRemaining) > 0.01) {
                $validator->errors()->add('settlement_remaining_balance', 'Sisa tagihan tidak sesuai dengan data invoice.');
            }

            $paidAmount = round((float) $this->input('settlement_paid_amount', 0), 2);
            $status = $this->input('settlement_payment_status');

            if ($status === 'paid_full') {
                if (abs($paidAmount - $remainingBalance) > 0.01) {
                    $validator->errors()->add('settlement_paid_amount', 'Nominal pelunasan harus sama dengan sisa tagihan untuk pembayaran lunas.');
                }
            } elseif ($status === 'paid_partial') {
                if ($paidAmount <= 0) {
                    $validator->errors()->add('settlement_paid_amount', 'Nominal pelunasan sebagian harus lebih dari 0.');
                }

                if ($paidAmount >= $remainingBalance) {
                    $validator->errors()->add('settlement_paid_amount', 'Nominal pelunasan sebagian tidak boleh melebihi sisa tagihan.');
                }
            }

            if ($validator->errors()->isEmpty()) {
                $this->referenceInvoice = $invoice;
            }
        });
    }

    public function referenceInvoice(): ?Invoice
    {
        return $this->referenceInvoice;
    }
}
