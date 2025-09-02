<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDebtPaymentRequest extends FormRequest
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
            'payment_amount' => 'required|numeric|min:0',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string',
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
            'payment_amount.required' => 'Jumlah pembayaran wajib diisi.',
            'payment_amount.numeric' => 'Jumlah pembayaran harus berupa angka.',
            'payment_amount.min' => 'Jumlah pembayaran tidak boleh kurang dari 0.',
            'payment_date.date' => 'Format tanggal tidak valid.',
        ];
    }
}
