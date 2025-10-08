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
            'package_id' => ['required', 'string'],
            'customer_type' => ['required', Rule::in($customerTypes)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $whatsapp = $this->input('client_whatsapp');

        if (is_string($whatsapp)) {
            $normalized = preg_replace('/[^\d+]/', '', $whatsapp);
            $this->merge(['client_whatsapp' => $normalized]);
        }
    }
}
