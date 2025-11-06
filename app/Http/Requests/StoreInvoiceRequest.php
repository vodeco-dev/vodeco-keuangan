<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use App\Support\PassThroughPackage;
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
        $transactionTypes = ['down_payment', 'full_payment', 'pass_through', 'settlement'];

        return [
            'transaction_type' => ['required', Rule::in($transactionTypes)],
            'client_name' => ['required_unless:transaction_type,settlement', 'nullable', 'string', 'max:255'],
            'client_whatsapp' => ['required_unless:transaction_type,settlement', 'nullable', 'string', 'max:32'],
            'client_address' => ['required_unless:transaction_type,settlement', 'nullable', 'string'],
            'issue_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'down_payment_due' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required_unless:transaction_type,settlement,pass_through', 'nullable', 'array', 'min:1'],
            'items.*.description' => ['required_unless:transaction_type,settlement,pass_through', 'string'],
            'items.*.quantity' => ['required_unless:transaction_type,settlement,pass_through', 'integer', 'min:1'],
            'items.*.price' => ['required_unless:transaction_type,settlement,pass_through', 'numeric'],
            'items.*.category_id' => [
                'required_unless:transaction_type,settlement,pass_through',
                'integer',
                Rule::exists('categories', 'id')->where('type', 'pemasukan'),
            ],
            'pass_through_package_id' => ['required_if:transaction_type,pass_through', 'nullable', 'string'],
            'pass_through_quantity' => ['required_if:transaction_type,pass_through', 'nullable', 'integer', 'min:1'],
            'pass_through_ad_budget_total' => ['nullable', 'numeric', 'min:0'],
            'pass_through_maintenance_total' => ['nullable', 'numeric', 'min:0'],
            'pass_through_account_creation_total' => ['nullable', 'numeric', 'min:0'],
            'pass_through_total_price' => ['nullable', 'numeric', 'min:0'],
            'pass_through_daily_balance_total' => ['nullable', 'numeric', 'min:0'],
            'pass_through_duration_days' => ['nullable', 'integer', 'min:0'],
            'pass_through_daily_balance_unit' => ['nullable', 'numeric', 'min:0'],
            'pass_through_ad_budget_unit' => ['nullable', 'numeric', 'min:0'],
            'pass_through_maintenance_unit' => ['nullable', 'numeric', 'min:0'],
            'pass_through_account_creation_unit' => ['nullable', 'numeric', 'min:0'],
            'pass_through_custom_customer_type' => [
                Rule::requiredIf(fn () => $this->input('transaction_type') === 'pass_through'
                    && $this->input('pass_through_package_id') === 'custom'),
                'nullable',
                'string',
                Rule::in([
                    PassThroughPackage::CUSTOMER_TYPE_NEW,
                    PassThroughPackage::CUSTOMER_TYPE_EXISTING,
                ]),
            ],
            'pass_through_custom_daily_balance' => [
                Rule::requiredIf(fn () => $this->input('transaction_type') === 'pass_through'
                    && $this->input('pass_through_package_id') === 'custom'),
                'nullable',
                'numeric',
                'min:1',
            ],
            'pass_through_custom_duration_days' => [
                Rule::requiredIf(fn () => $this->input('transaction_type') === 'pass_through'
                    && $this->input('pass_through_package_id') === 'custom'),
                'nullable',
                'integer',
                'min:1',
            ],
            'pass_through_custom_maintenance_fee' => [
                Rule::requiredIf(fn () => $this->input('transaction_type') === 'pass_through'
                    && $this->input('pass_through_package_id') === 'custom'),
                'nullable',
                'numeric',
                'min:0',
            ],
            'pass_through_custom_account_creation_fee' => [
                Rule::requiredIf(fn () => $this->input('transaction_type') === 'pass_through'
                    && $this->input('pass_through_custom_customer_type') === PassThroughPackage::CUSTOMER_TYPE_NEW),
                'nullable',
                'numeric',
                'min:0',
            ],
            'settlement_invoice_number' => ['required_if:transaction_type,settlement', 'nullable', 'string'],
            'settlement_remaining_balance' => ['required_if:transaction_type,settlement', 'nullable', 'numeric', 'min:0'],
            'settlement_payment_status' => [
                'required_if:transaction_type,settlement',
                'nullable',
                Rule::in(['paid_full', 'paid_partial']),
            ],
            'settlement_paid_amount' => ['required_if:transaction_type,settlement', 'nullable', 'numeric', 'min:0'],
            'payment_proof' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:5120'],
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
            'pass_through_package_id.required_if' => 'Paket Invoices Iklan wajib dipilih.',
            'pass_through_quantity.required_if' => 'Kuantitas paket wajib diisi.',
            'pass_through_quantity.integer' => 'Kuantitas paket harus berupa angka bulat.',
            'pass_through_quantity.min' => 'Kuantitas paket minimal 1.',
            'pass_through_ad_budget_total.numeric' => 'Total dana iklan harus berupa angka.',
            'pass_through_maintenance_total.numeric' => 'Total jasa maintenance harus berupa angka.',
            'pass_through_account_creation_total.numeric' => 'Total biaya pembuatan akun harus berupa angka.',
            'pass_through_total_price.numeric' => 'Total invoice Invoices Iklan harus berupa angka.',
            'pass_through_daily_balance_total.numeric' => 'Total saldo harian harus berupa angka.',
            'pass_through_duration_days.integer' => 'Durasi paket harus berupa angka bulat.',
            'pass_through_custom_customer_type.required_if' => 'Tipe pelanggan wajib dipilih untuk paket custom.',
            'pass_through_custom_customer_type.in' => 'Tipe pelanggan paket custom tidak valid.',
            'pass_through_custom_daily_balance.required_if' => 'Saldo harian wajib diisi untuk paket custom.',
            'pass_through_custom_daily_balance.numeric' => 'Saldo harian paket custom harus berupa angka.',
            'pass_through_custom_daily_balance.min' => 'Saldo harian paket custom minimal 1.',
            'pass_through_custom_duration_days.required_if' => 'Durasi tayang wajib diisi untuk paket custom.',
            'pass_through_custom_duration_days.integer' => 'Durasi tayang paket custom harus berupa angka.',
            'pass_through_custom_duration_days.min' => 'Durasi tayang paket custom minimal 1 hari.',
            'pass_through_custom_maintenance_fee.required_if' => 'Biaya maintenance wajib diisi untuk paket custom.',
            'pass_through_custom_maintenance_fee.numeric' => 'Biaya maintenance paket custom harus berupa angka.',
            'pass_through_custom_maintenance_fee.min' => 'Biaya maintenance paket custom minimal 0.',
            'pass_through_custom_account_creation_fee.required_if' => 'Biaya pembuatan akun wajib diisi untuk pelanggan baru pada paket custom.',
            'pass_through_custom_account_creation_fee.numeric' => 'Biaya pembuatan akun paket custom harus berupa angka.',
            'pass_through_custom_account_creation_fee.min' => 'Biaya pembuatan akun paket custom minimal 0.',
            'settlement_invoice_number.required_if' => 'Nomor invoice referensi wajib diisi untuk pelunasan.',
            'settlement_remaining_balance.required_if' => 'Sisa tagihan wajib diisi untuk pelunasan.',
            'settlement_remaining_balance.numeric' => 'Sisa tagihan harus berupa angka.',
            'settlement_payment_status.required_if' => 'Pilih status pembayaran pelunasan.',
            'settlement_payment_status.in' => 'Status pembayaran pelunasan tidak dikenal.',
            'settlement_paid_amount.required_if' => 'Nominal yang dibayarkan wajib diisi.',
            'settlement_paid_amount.numeric' => 'Nominal yang dibayarkan harus berupa angka.',
            'settlement_paid_amount.min' => 'Nominal yang dibayarkan minimal 0.',
            'payment_proof.image' => 'Bukti pembayaran harus berupa gambar.',
            'payment_proof.mimes' => 'Format bukti pembayaran harus PNG atau JPG.',
            'payment_proof.max' => 'Ukuran bukti pembayaran maksimal 5MB.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $transactionType = $this->input('transaction_type', 'down_payment');

        if ($transactionType !== 'pass_through') {
            foreach (array_keys($this->all()) as $key) {
                if (str_starts_with($key, 'pass_through_')) {
                    $this->request->remove($key);
                }
            }
        }

        $rawItems = $this->input('items', []);

        $items = collect($rawItems)->map(function ($item) {
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

        $merged = [
            'transaction_type' => $transactionType,
            'client_whatsapp' => $whatsapp,
            'down_payment_due' => $downPaymentDue,
            'settlement_remaining_balance' => $settlementRemaining,
            'settlement_paid_amount' => $settlementPaidAmount,
        ];

        if ($transactionType === 'pass_through') {
            $passThroughPackageId = $this->input('pass_through_package_id');
            if (is_string($passThroughPackageId)) {
                $passThroughPackageId = trim($passThroughPackageId);
                if ($passThroughPackageId === '') {
                    $passThroughPackageId = null;
                }
            }

            $isCustomPackage = $passThroughPackageId === 'custom';
            $customFieldKeys = [
                'pass_through_custom_customer_type',
                'pass_through_custom_daily_balance',
                'pass_through_custom_duration_days',
                'pass_through_custom_maintenance_fee',
                'pass_through_custom_account_creation_fee',
            ];

            $passThroughFields = [
                'pass_through_package_id' => $passThroughPackageId,
                'pass_through_quantity' => $this->sanitizeInteger($this->input('pass_through_quantity')),
                'pass_through_ad_budget_total' => $this->sanitizeCurrency($this->input('pass_through_ad_budget_total')),
                'pass_through_maintenance_total' => $this->sanitizeCurrency($this->input('pass_through_maintenance_total')),
                'pass_through_account_creation_total' => $this->sanitizeCurrency($this->input('pass_through_account_creation_total')),
                'pass_through_total_price' => $this->sanitizeCurrency($this->input('pass_through_total_price')),
                'pass_through_daily_balance_total' => $this->sanitizeCurrency($this->input('pass_through_daily_balance_total')),
                'pass_through_duration_days' => $this->sanitizeInteger($this->input('pass_through_duration_days')),
                'pass_through_daily_balance_unit' => $this->sanitizeCurrency($this->input('pass_through_daily_balance_unit')),
                'pass_through_ad_budget_unit' => $this->sanitizeCurrency($this->input('pass_through_ad_budget_unit')),
                'pass_through_maintenance_unit' => $this->sanitizeCurrency($this->input('pass_through_maintenance_unit')),
                'pass_through_account_creation_unit' => $this->sanitizeCurrency($this->input('pass_through_account_creation_unit')),
            ];

            $customPassThroughFields = [];

            if ($isCustomPackage) {
                $customCustomerType = $this->input('pass_through_custom_customer_type');
                if (is_string($customCustomerType)) {
                    $customCustomerType = trim($customCustomerType);
                    if ($customCustomerType !== '') {
                        $normalizedType = strtolower($customCustomerType);
                        if (in_array($normalizedType, [PassThroughPackage::CUSTOMER_TYPE_NEW, PassThroughPackage::CUSTOMER_TYPE_EXISTING], true)) {
                            $customCustomerType = $normalizedType;
                        }
                    } else {
                        $customCustomerType = null;
                    }
                }

                $customPassThroughFields = [
                    'pass_through_custom_customer_type' => $customCustomerType,
                    'pass_through_custom_daily_balance' => $this->sanitizeCurrency($this->input('pass_through_custom_daily_balance')),
                    'pass_through_custom_duration_days' => $this->sanitizeInteger($this->input('pass_through_custom_duration_days')),
                    'pass_through_custom_maintenance_fee' => $this->sanitizeCurrency($this->input('pass_through_custom_maintenance_fee')),
                    'pass_through_custom_account_creation_fee' => $this->sanitizeCurrency($this->input('pass_through_custom_account_creation_fee')),
                ];
            } else {
                foreach ($customFieldKeys as $field) {
                    $this->request->remove($field);
                }
            }

            $merged = array_merge($merged, array_filter($passThroughFields, fn ($value) => $value !== null));

            if ($isCustomPackage) {
                $merged = array_merge($merged, array_filter($customPassThroughFields, fn ($value) => $value !== null));
            }
        }

        if (in_array($transactionType, ['settlement', 'pass_through'])) {
            $this->request->remove('items');
        } else {
            $merged['items'] = $items->toArray();
        }

        $this->merge($merged);
    }

    protected function sanitizeCurrency($value): ?string
    {
        if (! isset($value)) {
            return null;
        }

        $normalized = preg_replace('/[^\d,.-]/', '', (string) $value);
        $normalized = str_replace('.', '', $normalized);
        $normalized = str_replace(',', '.', $normalized);

        return $normalized === '' ? null : $normalized;
    }

    protected function sanitizeInteger($value): ?int
    {
        if (! isset($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        $digits = preg_replace('/\D/', '', (string) $value);

        return $digits === '' ? null : (int) $digits;
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
