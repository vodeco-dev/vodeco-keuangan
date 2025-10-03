<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
{
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
        return [
            'customer_service_id' => ['nullable', 'exists:customer_services,id'],
            'client_name' => ['required', 'string', 'max:255'],
            'client_whatsapp' => ['required', 'string', 'max:32'],
            'client_address' => ['required', 'string'],
            'issue_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'down_payment_due' => ['nullable', 'numeric', 'min:0'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric'],
            'items.*.category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where('type', 'pemasukan'),
            ],
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
            'client_name.required' => 'Nama klien wajib diisi.',
            'client_whatsapp.required' => 'Nomor WhatsApp klien wajib diisi.',
            'client_whatsapp.max' => 'Nomor WhatsApp klien terlalu panjang.',
            'client_address.required' => 'Alamat klien wajib diisi.',
            'issue_date.date' => 'Format tanggal terbit tidak valid.',
            'due_date.date' => 'Format tanggal jatuh tempo tidak valid.',
            'down_payment_due.numeric' => 'Rencana down payment harus berupa angka.',
            'down_payment_due.min' => 'Rencana down payment minimal 0.',
            'customer_service_id.exists' => 'Customer service yang dipilih tidak valid.',
            'items.*.description.required' => 'Deskripsi item wajib diisi.',
            'items.*.description.string' => 'Deskripsi item harus berupa teks.',
            'items.*.quantity.required' => 'Kuantitas item wajib diisi.',
            'items.*.quantity.integer' => 'Kuantitas item harus berupa angka.',
            'items.*.quantity.min' => 'Kuantitas item minimal 1.',
            'items.*.price.required' => 'Harga item wajib diisi.',
            'items.*.price.numeric' => 'Harga item harus berupa angka.',
            'items.*.category_id.required' => 'Kategori item wajib dipilih.',
            'items.*.category_id.exists' => 'Kategori item tidak valid.',
        ];
    }

    protected function prepareForValidation(): void
    {
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

        $this->merge([
            'items' => $items->toArray(),
            'client_whatsapp' => $whatsapp,
            'down_payment_due' => $downPaymentDue,
        ]);
    }
}
