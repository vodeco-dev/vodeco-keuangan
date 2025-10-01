<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'customer_service_id' => ['nullable', 'exists:customer_services,id'],
            'client_name' => ['required', 'string', 'max:255'],
            'client_email' => ['required', 'email', 'max:255'],
            'client_address' => ['required', 'string'],
            'issue_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'client_name.required' => 'Nama klien wajib diisi.',
            'client_email.required' => 'Email klien wajib diisi.',
            'client_email.email' => 'Format email klien tidak valid.',
            'client_address.required' => 'Alamat klien wajib diisi.',
            'issue_date.date' => 'Format tanggal terbit tidak valid.',
            'due_date.date' => 'Format tanggal jatuh tempo tidak valid.',
            'customer_service_id.exists' => 'Customer service yang dipilih tidak valid.',
            'items.*.description.required' => 'Deskripsi item wajib diisi.',
            'items.*.description.string' => 'Deskripsi item harus berupa teks.',
            'items.*.quantity.required' => 'Kuantitas item wajib diisi.',
            'items.*.quantity.integer' => 'Kuantitas item harus berupa angka.',
            'items.*.quantity.min' => 'Kuantitas item minimal 1.',
            'items.*.price.required' => 'Harga item wajib diisi.',
            'items.*.price.numeric' => 'Harga item harus berupa angka.',
        ];
    }
}
