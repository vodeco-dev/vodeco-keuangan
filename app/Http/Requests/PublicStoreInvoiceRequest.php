<?php

namespace App\Http\Requests;

use App\Models\InvoicePortalPassphrase;
use App\Support\PassThroughPackage;
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
        $rules = parent::rules();

        if ($this->isPassThroughEnabled()) {
            $rules['items'] = ['nullable', 'array'];
            $rules['items.*.description'] = ['nullable', 'string'];
            $rules['items.*.quantity'] = ['nullable', 'integer', 'min:1'];
            $rules['items.*.price'] = ['nullable', 'numeric'];
            $rules['items.*.category_id'] = ['nullable', 'integer'];
        }

        return array_merge($rules, [
            'passphrase_token' => ['required', 'string'],
            'pass_through_enabled' => ['sometimes', 'boolean'],
            'pass_through_customer_type' => [
                'required_if:pass_through_enabled,1',
                'nullable',
                Rule::in([
                    PassThroughPackage::CUSTOMER_TYPE_NEW,
                    PassThroughPackage::CUSTOMER_TYPE_EXISTING,
                ]),
            ],
            'pass_through_package_id' => ['required_if:pass_through_enabled,1', 'nullable', 'string'],
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
            'passphrase_token.required' => 'Verifikasi passphrase portal invoice sebelum mengirim formulir.',
            'pass_through_customer_type.required_if' => 'Pilih jenis pelanggan untuk invoice pass through.',
            'pass_through_customer_type.in' => 'Jenis pelanggan pass through tidak dikenal.',
            'pass_through_package_id.required_if' => 'Pilih paket pass through yang ingin digunakan.',
        ]);
    }

    protected function prepareForValidation(): void
    {
        $passThroughEnabled = $this->boolean('pass_through_enabled');

        $this->merge([
            'pass_through_enabled' => $passThroughEnabled,
        ]);

        if ($passThroughEnabled) {
            $this->merge([
                'transaction_type' => 'pass_through',
            ]);
        }

        parent::prepareForValidation();
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

            if ($transactionType === 'pass_through' && ! $this->isPassThroughEnabled()) {
                $validator->errors()->add('transaction_type', 'Aktifkan paket pass through sebelum mengirim formulir.');

                return;
            }

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

    protected function isPassThroughEnabled(): bool
    {
        $value = $this->input('pass_through_enabled');

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
