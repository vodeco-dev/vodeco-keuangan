<?php

namespace App\Http\Requests;

use App\Enums\InvoicePortalPassphraseAccessType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoicePortalPassphraseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'access_type' => ['required', Rule::enum(InvoicePortalPassphraseAccessType::class)],
            'label' => ['required', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'passphrase' => ['nullable', 'string', 'min:12'],
        ];
    }

    public function messages(): array
    {
        return [
            'access_type.required' => 'Pilih tipe akses yang akan digunakan.',
            'access_type.enum' => 'Tipe akses tidak valid.',
            'label.required' => 'Masukkan nama pemilik passphrase.',
            'label.max' => 'Nama pemilik passphrase terlalu panjang.',
            'expires_at.date' => 'Format tanggal kedaluwarsa tidak valid.',
            'expires_at.after' => 'Tanggal kedaluwarsa harus di masa depan.',
            'passphrase.min' => 'Passphrase minimal 12 karakter untuk keamanan.',
        ];
    }
}
