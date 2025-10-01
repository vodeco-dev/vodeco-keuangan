<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Validation\Rule;

class PublicStoreInvoiceRequest extends StoreInvoiceRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'customer_service_name' => [
                'required',
                'string',
                Rule::exists('users', 'name')->where(function ($query) {
                    $query->whereIn('role', [
                        Role::ADMIN->value,
                        Role::ACCOUNTANT->value,
                        Role::STAFF->value,
                    ]);
                }),
            ],
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
            'customer_service_name.required' => 'Masukkan nama customer service yang akan menangani invoice ini.',
            'customer_service_name.exists' => 'Nama customer service tidak ditemukan.',
        ]);
    }
}
