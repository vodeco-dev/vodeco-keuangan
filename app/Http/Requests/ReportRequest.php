<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class ReportRequest extends FormRequest
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
            'start_date' => ['required', 'date', 'before_or_equal:end_date'],
            'end_date' => ['required', 'date'],
            'format' => ['nullable', 'in:xlsx'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'type' => ['nullable', 'in:pemasukan,pengeluaran'],
            'period' => ['nullable', 'in:range,daily,monthly,yearly'],
            'date' => ['nullable', 'date', 'required_if:period,daily'],
            'month' => ['nullable', 'integer', 'between:1,12', 'required_if:period,monthly'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:2100', 'required_if:period,monthly,yearly'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $fieldsToNormalize = [
            'category_id',
            'type',
            'period',
            'date',
            'month',
            'year',
            'format',
            'start_date',
            'end_date',
        ];

        $normalized = [];

        foreach ($fieldsToNormalize as $field) {
            if ($this->has($field) && $this->input($field) === '') {
                $normalized[$field] = null;
            }
        }

        if (! empty($normalized)) {
            $this->merge($normalized);
        }

        $period = $this->input('period') ?: 'range';

        $startDate = $this->input('start_date');
        $endDate = $this->input('end_date');

        switch ($period) {
            case 'daily':
                $date = $this->input('date') ?: Carbon::now()->toDateString();
                try {
                    $parsed = Carbon::parse($date);
                } catch (\Exception $e) {
                    $parsed = Carbon::now();
                }
                $startDate = $parsed->copy()->startOfDay()->toDateString();
                $endDate = $parsed->copy()->endOfDay()->toDateString();
                break;
            case 'monthly':
                $monthInput = $this->input('month');
                $month = is_null($monthInput) ? Carbon::now()->month : (int) $monthInput;

                $yearInput = $this->input('year');
                $year = is_null($yearInput) ? Carbon::now()->year : (int) $yearInput;
                if ($month < 1 || $month > 12) {
                    $month = Carbon::now()->month;
                }
                if ($year < 1900 || $year > 2100) {
                    $year = Carbon::now()->year;
                }
                $startDate = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
                $endDate = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();
                break;
            case 'yearly':
                $yearInput = $this->input('year');
                $year = is_null($yearInput) ? Carbon::now()->year : (int) $yearInput;
                if ($year < 1900 || $year > 2100) {
                    $year = Carbon::now()->year;
                }
                $startDate = Carbon::create($year, 1, 1)->startOfYear()->toDateString();
                $endDate = Carbon::create($year, 12, 31)->endOfYear()->toDateString();
                break;
            case 'range':
            default:
                $startDate = $startDate ?? Carbon::now()->startOfMonth()->toDateString();
                $endDate = $endDate ?? Carbon::now()->endOfMonth()->toDateString();
                break;
        }

        $this->merge([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'period' => $period,
        ]);
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'start_date.date' => 'Tanggal mulai tidak valid.',
            'start_date.before_or_equal' => 'Tanggal mulai tidak boleh lebih besar dari tanggal akhir.',
            'end_date.required' => 'Tanggal akhir wajib diisi.',
            'end_date.date' => 'Tanggal akhir tidak valid.',
            'format.in' => 'Format harus berupa file Excel (.xlsx).',
            'category_id.exists' => 'Kategori yang dipilih tidak ditemukan.',
            'type.in' => 'Tipe transaksi tidak valid.',
            'period.in' => 'Jenis rentang waktu tidak valid.',
            'date.required_if' => 'Tanggal wajib diisi untuk filter harian.',
            'date.date' => 'Tanggal harian tidak valid.',
            'month.required_if' => 'Bulan wajib diisi untuk filter bulanan.',
            'month.integer' => 'Bulan tidak valid.',
            'month.between' => 'Bulan harus antara 1 sampai 12.',
            'year.required_if' => 'Tahun wajib diisi untuk filter bulanan atau tahunan.',
            'year.integer' => 'Tahun tidak valid.',
            'year.min' => 'Tahun tidak valid.',
            'year.max' => 'Tahun tidak valid.',
        ];
    }
}

