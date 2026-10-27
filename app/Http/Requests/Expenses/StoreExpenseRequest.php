<?php

namespace App\Http\Requests\Expenses;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'category'    => 'required|in:Fuel,Salary,Transport,Utility,Misc',
            'description' => 'required|string|max:500',
            'amount'      => 'required|numeric|min:0.01',
            'date'        => 'required|date',
            'payment_method' => 'nullable|in:Cash,Bank Transfer',
        ];
    }
}
