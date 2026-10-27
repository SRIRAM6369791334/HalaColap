<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'login' => mb_strtolower(trim((string) $this->input('login'))),
            'business_code' => mb_strtolower(trim((string) $this->input('business_code'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'business_code' => 'nullable|string|max:100|regex:/^[a-z0-9-]+$/',
            'login' => 'required|string|max:255',
            'password' => 'required|string|max:255',
        ];
    }
}
