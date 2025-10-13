<?php

namespace App\Http\Requests;

use App\Support\PassThroughPackage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePassThroughInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $customerTypes = [
            PassThroughPackage::CUSTOMER_TYPE_NEW,
            PassThroughPackage::CUSTOMER_TYPE_EXISTING,
        ];

        return [
            'client_name' => ['required', 'string', 'max:255'],
            'client_whatsapp' => ['required', 'string', 'max:32'],
            'client_address' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'customer_type' => ['required', Rule::in($customerTypes)],
            'daily_balance' => ['required', 'numeric', 'min:0'],
            'estimated_duration' => ['required', 'integer', 'min:1'],
            'maintenance_fee' => ['required', 'numeric', 'min:0'],
            'account_creation_fee' => [
                'nullable',
                'numeric',
                'min:0',
                Rule::requiredIf(fn () => $this->input('customer_type') === PassThroughPackage::CUSTOMER_TYPE_NEW),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $whatsapp = $this->input('client_whatsapp');

        if (is_string($whatsapp)) {
            $normalized = preg_replace('/[^\d+]/', '', $whatsapp);
            $this->merge(['client_whatsapp' => $normalized]);
        }

        $currencyFields = ['daily_balance', 'maintenance_fee', 'account_creation_fee'];

        foreach ($currencyFields as $field) {
            $value = $this->input($field);

            if (! isset($value)) {
                continue;
            }

            $normalized = preg_replace('/[^\d,.-]/', '', (string) $value);
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);

            $this->merge([$field => $normalized === '' ? null : $normalized]);
        }

        $duration = $this->input('estimated_duration');

        if (isset($duration) && ! is_numeric($duration)) {
            $digits = preg_replace('/\D/', '', (string) $duration);
            $this->merge(['estimated_duration' => $digits !== '' ? (int) $digits : null]);
        }
    }
}
