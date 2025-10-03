<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RotateInvoicePortalPassphraseRequest extends FormRequest
{
    protected $errorBag = 'rotatePassphrase';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'passphrase' => ['nullable', 'string', 'min:12'],
        ];
    }

    public function messages(): array
    {
        return [
            'label.max' => 'Nama pemilik passphrase terlalu panjang.',
            'expires_at.date' => 'Format tanggal kedaluwarsa tidak valid.',
            'expires_at.after' => 'Tanggal kedaluwarsa harus di masa depan.',
            'passphrase.min' => 'Passphrase minimal 12 karakter untuk keamanan.',
        ];
    }
}
