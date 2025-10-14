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
            'daily_balance' => ['required', 'numeric', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'maintenance_fee' => ['required', 'numeric', 'min:0'],
            'account_creation_fee' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $numericFields = [
            'daily_balance',
            'maintenance_fee',
            'account_creation_fee',
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

        $duration = $this->input('duration_days');

        if ($duration !== null) {
            if (is_string($duration)) {
                $normalizedDuration = preg_replace('/\D/', '', $duration);
                $mutated['duration_days'] = $normalizedDuration === '' ? null : (int) $normalizedDuration;
            } elseif (is_float($duration)) {
                $mutated['duration_days'] = (int) round($duration);
            }
        }

        if (! empty($mutated)) {
            $this->merge($mutated);
        }
    }
}
