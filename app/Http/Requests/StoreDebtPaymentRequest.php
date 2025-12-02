<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDebtPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_amount' => 'required|numeric|min:0',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_amount.required' => 'Jumlah pembayaran wajib diisi.',
            'payment_amount.numeric' => 'Jumlah pembayaran harus berupa angka.',
            'payment_amount.min' => 'Jumlah pembayaran tidak boleh kurang dari 0.',
            'payment_date.date' => 'Format tanggal tidak valid.',
            'category_id.integer' => 'Kategori tidak valid.',
            'category_id.exists' => 'Kategori tidak ditemukan.',
        ];
    }
}
