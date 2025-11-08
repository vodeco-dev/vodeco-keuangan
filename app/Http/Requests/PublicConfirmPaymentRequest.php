<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use App\Models\InvoicePortalPassphrase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class PublicConfirmPaymentRequest extends FormRequest
{
    protected ?InvoicePortalPassphrase $passphrase = null;

    protected ?Invoice $invoice = null;

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
            'passphrase_token' => ['required', 'string'],
            'invoice_number' => ['required', 'string'],
            'payment_proof' => ['required', 'image', 'mimes:png,jpg,jpeg', 'mimetypes:image/png,image/jpeg,image/jpg', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'passphrase_token.required' => 'Verifikasi passphrase portal invoice sebelum mengirim formulir.',
            'invoice_number.required' => 'Nomor invoice wajib diisi.',
            'payment_proof.required' => 'Unggah bukti pembayaran sebelum melanjutkan.',
            'payment_proof.image' => 'Bukti pembayaran harus berupa gambar.',
            'payment_proof.mimes' => 'Format bukti pembayaran harus PNG atau JPG.',
            'payment_proof.max' => 'Ukuran bukti pembayaran maksimal 5MB.',
        ];
    }

    public function withValidator($validator): void
    {
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

            $number = $this->input('invoice_number');
            if (! $number) {
                return;
            }

            $invoice = Invoice::query()
                ->where('number', $number)
                ->where('type', '!=', Invoice::TYPE_SETTLEMENT)
                ->first();

            if (! $invoice) {
                $validator->errors()->add('invoice_number', 'Invoice tidak ditemukan.');

                return;
            }

            if (! $passphrase->canManageInvoice($invoice)) {
                $validator->errors()->add('invoice_number', 'Invoice tidak terdaftar pada akun Anda atau tidak dapat dikonfirmasi.');

                return;
            }

            $this->passphrase = $passphrase;
            $this->invoice = $invoice;
        });
    }

    public function passphrase(): ?InvoicePortalPassphrase
    {
        return $this->passphrase;
    }

    public function invoice(): ?Invoice
    {
        return $this->invoice;
    }
}
