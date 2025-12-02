<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => 'required|date',
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string|max:255',
            'proof' => 'nullable|image|mimes:png,jpg,jpeg|mimetypes:image/png,image/jpeg,image/jpg|max:250',
            'proof_name' => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'Tanggal wajib diisi.',
            'date.date' => 'Format tanggal tidak valid.',
            'category_id.required' => 'Kategori wajib diisi.',
            'category_id.exists' => 'Kategori tidak valid.',
            'amount.required' => 'Jumlah wajib diisi.',
            'amount.numeric' => 'Jumlah harus berupa angka.',
            'amount.min' => 'Jumlah tidak boleh kurang dari 0.',
            'description.required' => 'Deskripsi wajib diisi.',
            'description.string' => 'Deskripsi harus berupa teks.',
            'description.max' => 'Deskripsi tidak boleh lebih dari 255 karakter.',
            'proof.image' => 'Bukti transaksi harus berupa gambar.',
            'proof.max' => 'Ukuran bukti transaksi maksimal 250KB.',
            'proof_name.string' => 'Nama file bukti transaksi harus berupa teks.',
            'proof_name.max' => 'Nama file bukti transaksi tidak boleh lebih dari 100 karakter.',
        ];
    }
}
