<?php

namespace App\Http\Requests;

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
            'customer_service_id' => ['required', 'exists:users,id'],
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
            'customer_service_id.required' => 'Pilih customer service yang akan menangani invoice ini.',
            'customer_service_id.exists' => 'Customer service yang dipilih tidak ditemukan.',
        ]);
    }
}
