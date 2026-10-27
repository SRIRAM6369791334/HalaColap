<?php

namespace App\Http\Requests\Payments;

use Illuminate\Foundation\Http\FormRequest;

class StoreDealerPaymentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        $cashAmount = $this->input('cash_amount');
        $bankAmount = $this->input('bank_amount');
        $amount = $this->input('amount');
        $paymentMode = $this->input('payment_mode');

        if ($cashAmount === null && $bankAmount === null && $amount === null) {
            return;
        }

        if ($cashAmount === null && $bankAmount === null && $amount !== null) {
            $amountVal = (float) $amount;
            if ($paymentMode === 'Cash') {
                $cashAmount = $amountVal;
                $bankAmount = 0.00;
            } else {
                $cashAmount = 0.00;
                $bankAmount = $amountVal;
            }
        } else {
            $cashAmount = (float) ($cashAmount ?? 0);
            $bankAmount = (float) ($bankAmount ?? 0);
            $amount = round($cashAmount + $bankAmount, 2);
        }

        if (!$paymentMode || $paymentMode === 'Bank Transfer') {
            $paymentMode = ((float) ($bankAmount ?? 0) > 0)
                ? ($this->input('bank_transfer_type') ?: 'Bank Transfer')
                : 'Cash';
        }

        $this->merge([
            'cash_amount'  => $cashAmount,
            'bank_amount'  => $bankAmount,
            'amount'       => $amount,
            'payment_mode' => $paymentMode,
        ]);
    }

    public function rules(): array
    {
        return [
            'dealer_id'          => [
                'required',
                \Illuminate\Validation\Rule::exists('dealers', 'id')->where(fn($q) => (app(\App\Services\Tenancy\TenantContext::class)->getTenantId() ?? auth()->user()?->current_tenant_id) ? $q->where('tenant_id', (app(\App\Services\Tenancy\TenantContext::class)->getTenantId() ?? auth()->user()?->current_tenant_id)) : $q),
            ],
            'weekly_bill_id'     => [
                'nullable',
                \Illuminate\Validation\Rule::exists('weekly_bills', 'id')->where(fn($q) => (app(\App\Services\Tenancy\TenantContext::class)->getTenantId() ?? auth()->user()?->current_tenant_id) ? $q->where('tenant_id', (app(\App\Services\Tenancy\TenantContext::class)->getTenantId() ?? auth()->user()?->current_tenant_id)) : $q),
            ],
            'payment_part'       => 'nullable|in:monday,friday',
            'selected_entry_ids' => 'nullable|array',
            'selected_entry_ids.*' => [
                \Illuminate\Validation\Rule::exists('day_load_entries', 'id')->where(fn($q) => (app(\App\Services\Tenancy\TenantContext::class)->getTenantId() ?? auth()->user()?->current_tenant_id) ? $q->where('tenant_id', (app(\App\Services\Tenancy\TenantContext::class)->getTenantId() ?? auth()->user()?->current_tenant_id)) : $q),
            ],
            'selected_emi_ids'   => 'nullable|array',
            'selected_emi_ids.*' => [
                \Illuminate\Validation\Rule::exists('emis', 'id')->where(fn($q) => (app(\App\Services\Tenancy\TenantContext::class)->getTenantId() ?? auth()->user()?->current_tenant_id) ? $q->where('tenant_id', (app(\App\Services\Tenancy\TenantContext::class)->getTenantId() ?? auth()->user()?->current_tenant_id)) : $q),
            ],
            'cash_amount'        => 'required|numeric|min:0',
            'bank_amount'        => 'required|numeric|min:0',
            'amount'             => [
                'required',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) {
                    $dealer = \App\Models\Dealer::find($this->input('dealer_id'));
                    if ($dealer && empty($this->input('selected_emi_ids'))) {
                        $allowedMax = $dealer->displayed_outstanding;
                        if ($allowedMax > 0 && $value > $allowedMax) {
                            $fail("The payout amount cannot exceed the dealer's pending balance of Rs " . number_format($allowedMax, 2) . ".");
                        }
                    }
                }
            ],
            'payment_mode'       => 'required|in:Cash,Bank Transfer,UPI,NEFT,RTGS,IMPS,Cheque,Other',
            'bank_transfer_type' => 'nullable|required_if:bank_amount,>0|in:UPI,Bank Transfer,NEFT,RTGS,IMPS,Cheque,Other',
            'date'               => 'required|date|before_or_equal:today',
            'notes'              => 'nullable|string|max:500',
        ];
    }
}
