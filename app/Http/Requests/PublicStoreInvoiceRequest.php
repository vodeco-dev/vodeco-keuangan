<?php

namespace App\Http\Requests;

use App\Models\InvoicePortalPassphrase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;
use Throwable;

class PublicStoreInvoiceRequest extends StoreInvoiceRequest
{
    protected ?InvoicePortalPassphrase $passphrase = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'customer_service_name' => [
                'required_unless:transaction_type,settlement',
                'string',
                'max:255',
                Rule::exists('customer_services', 'name'),
            ],
            'passphrase_token' => ['required', 'string'],
        ]);
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'customer_service_name.required_unless' => 'Masukkan nama customer service untuk transaksi selain pelunasan.',
            'customer_service_name.exists' => 'Nama customer service tidak ditemukan.',
            'passphrase_token.required' => 'Verifikasi passphrase portal invoice sebelum mengirim formulir.',
        ]);
    }

    public function withValidator($validator): void
    {
        parent::withValidator($validator);

        $validator->after(function ($validator) {
            $token = $this->input('passphrase_token');

            if (! $token) {
                return;
            }

            try {
                $passphraseId = (int) Crypt::decryptString($token);
            } catch (Throwable $exception) {
                $validator->errors()->add('passphrase_token', 'Passphrase tidak valid atau telah berubah.');

                return;
            }

            $passphrase = InvoicePortalPassphrase::find($passphraseId);

            if (! $passphrase || ! $passphrase->isUsable()) {
                $validator->errors()->add('passphrase_token', 'Passphrase sudah tidak aktif atau kedaluwarsa.');

                return;
            }

            $session = (array) $this->session()->get('invoice_portal_passphrase', []);

            if ((int) ($session['id'] ?? 0) !== $passphrase->id) {
                $validator->errors()->add('passphrase_token', 'Passphrase tidak sesuai dengan sesi aktif.');

                return;
            }

            $transactionType = $this->input('transaction_type', 'down_payment');
            $allowedTypes = $passphrase->allowedTransactionTypes();

            if (! in_array($transactionType, $allowedTypes, true)) {
                $validator->errors()->add('transaction_type', 'Transaksi ini tidak diizinkan oleh passphrase yang digunakan.');

                return;
            }

            $this->passphrase = $passphrase;
        });
    }

    public function passphrase(): ?InvoicePortalPassphrase
    {
        return $this->passphrase;
    }
}
