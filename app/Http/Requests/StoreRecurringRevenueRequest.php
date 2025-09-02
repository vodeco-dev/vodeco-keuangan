<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecurringRevenueRequest extends FormRequest
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
            'category_id' => 'nullable|exists:categories,id',
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric',
            'frequency' => 'required|string',
            'next_run' => 'required|date',
            'description' => 'nullable|string',
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
            'category_id.exists' => 'Kategori tidak valid.',
            'user_id.required' => 'User wajib diisi.',
            'user_id.exists' => 'User tidak valid.',
            'amount.required' => 'Jumlah wajib diisi.',
            'amount.numeric' => 'Jumlah harus berupa angka.',
            'frequency.required' => 'Frekuensi wajib diisi.',
            'frequency.string' => 'Frekuensi harus berupa teks.',
            'next_run.required' => 'Tanggal berjalan berikutnya wajib diisi.',
            'next_run.date' => 'Format tanggal berjalan berikutnya tidak valid.',
            'description.string' => 'Deskripsi harus berupa teks.',
        ];
    }
}
