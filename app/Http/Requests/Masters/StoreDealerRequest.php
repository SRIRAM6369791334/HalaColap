<?php

namespace App\Http\Requests\Masters;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDealerRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $dealerId = $this->route('dealer') ? $this->route('dealer')->id : null;

        $tenantId = app(\App\Services\Tenancy\TenantContext::class)->getTenantId() ?? auth()->user()?->current_tenant_id;

        return [
            'firm_name'      => 'required|string|max:255',
            'sort_order'     => 'nullable|integer|min:1',
            'phone'          => [
                'required',
                'string',
                'regex:/^[6-9]\d{9}$/',
                Rule::unique('dealers', 'phone')->ignore($dealerId)->where(fn($q) => $tenantId ? $q->where('tenant_id', $tenantId) : $q)->whereNull('deleted_at'),
            ],
            'contact_person' => 'nullable|string|max:255',
            'gst_number'     => 'nullable|string|max:20',
            'location'       => 'nullable|string|max:255',
            'route'          => 'nullable|string|max:255',
            'route_id'       => [
                'nullable',
                Rule::exists('routes', 'id')->where(fn($q) => $tenantId ? $q->where('tenant_id', $tenantId) : $q),
            ],
            'pending_amount' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'The phone number field is required.',
            'phone.regex'    => 'Please enter a valid 10-digit mobile number (e.g. 9876543210).',
            'phone.unique'   => 'This phone number has already been registered.',
        ];
    }
}
