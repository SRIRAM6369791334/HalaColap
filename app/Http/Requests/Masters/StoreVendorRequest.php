<?php

namespace App\Http\Requests\Masters;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVendorRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $vendorId = $this->route('vendor') ? $this->route('vendor')->id : null;

        return [
            'firm_name'      => 'required|string|max:255',
            'phone'          => [
                'required',
                'string',
                'regex:/^[6-9]\d{9}$/',
                Rule::unique('vendors', 'phone')->ignore($vendorId)->where(fn($q) => (app(\App\Services\Tenancy\TenantContext::class)->getTenantId() ?? auth()->user()?->current_tenant_id) ? $q->where('tenant_id', (app(\App\Services\Tenancy\TenantContext::class)->getTenantId() ?? auth()->user()?->current_tenant_id)) : $q),
            ],
            'contact_person' => 'nullable|string|max:255',
            'gst_number'     => 'nullable|string|max:20',
            'location'       => 'nullable|string|max:255',
            'route'          => 'nullable|string|max:100',
            'notes'          => 'nullable|string|max:1000',
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
