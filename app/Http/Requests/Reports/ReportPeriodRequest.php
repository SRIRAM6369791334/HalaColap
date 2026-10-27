<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;

class ReportPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start' => ['nullable', 'date'],
            'end'   => ['nullable', 'date', 'after_or_equal:start'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year'  => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ];
    }
}
