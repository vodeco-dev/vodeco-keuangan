<?php

namespace App\Http\Requests;

use App\Models\Debt;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDebtRequest extends FormRequest
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
            'description' => 'required|string|max:255',
            'related_party' => 'required|string|max:255',
            'type' => 'required|in:' . Debt::TYPE_PASS_THROUGH . ',' . Debt::TYPE_DOWN_PAYMENT,
            'amount' => 'required|numeric|min:0',
            'due_date' => 'nullable|date',
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(function ($query) {
                    $type = $this->input('type');
                    $expectedCategoryType = $type === Debt::TYPE_DOWN_PAYMENT ? 'pemasukan' : 'pengeluaran';

                    $query->where('type', $expectedCategoryType);
                }),
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
            'description.required' => 'Deskripsi wajib diisi.',
            'description.max' => 'Deskripsi tidak boleh lebih dari 255 karakter.',
            'related_party.required' => 'Pihak terkait wajib diisi.',
            'related_party.max' => 'Pihak terkait tidak boleh lebih dari 255 karakter.',
            'type.required' => 'Tipe wajib diisi.',
            'type.in' => 'Tipe tidak valid.',
            'amount.required' => 'Jumlah wajib diisi.',
            'amount.numeric' => 'Jumlah harus berupa angka.',
            'amount.min' => 'Jumlah tidak boleh kurang dari 0.',
            'due_date.date' => 'Format tanggal tidak valid.',
            'category_id.required' => 'Kategori wajib dipilih.',
            'category_id.integer' => 'Kategori tidak valid.',
            'category_id.exists' => 'Kategori tidak ditemukan atau tidak sesuai dengan tipe.',
        ];
    }
}
