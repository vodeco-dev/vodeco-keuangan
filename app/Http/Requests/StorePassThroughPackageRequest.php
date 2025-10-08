<?php

namespace App\Http\Requests;

use App\Support\PassThroughPackage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePassThroughPackageRequest extends FormRequest
{
    protected $errorBag = 'passThroughPackage';

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
            'name' => ['required', 'string', 'max:255'],
            'customer_type' => ['required', Rule::in($customerTypes)],
            'package_price' => ['required', 'numeric', 'min:0'],
            'daily_deduction' => ['required', 'numeric', 'min:0'],
            'maintenance_fee' => ['required', 'numeric', 'min:0'],
            'account_creation_fee' => ['nullable', 'numeric', 'min:0'],
            'renewal_fee' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $numericFields = [
            'package_price',
            'daily_deduction',
            'maintenance_fee',
            'account_creation_fee',
            'renewal_fee',
        ];

        $mutated = [];

        foreach ($numericFields as $field) {
            $value = $this->input($field);

            if ($value === null) {
                continue;
            }

            if (is_string($value)) {
                $normalized = preg_replace('/[^\d,.-]/', '', $value);
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
                $mutated[$field] = $normalized === '' ? null : $normalized;
            }
        }

        if (! empty($mutated)) {
            $this->merge($mutated);
        }
    }
}
