<?php

namespace App\Http\Requests\Payments;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerPaymentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        $codAmount = $this->input('cod_amount');
        $bankTransferAmount = $this->input('bank_transfer_amount');
        $amount = $this->input('amount');
        $paymentMode = $this->input('payment_mode');

        if ($codAmount === null && $bankTransferAmount === null && $amount === null) {
            return;
        }

        if ($codAmount === null && $bankTransferAmount === null && $amount !== null) {
            $amountVal = (float) $amount;
            if ($paymentMode === 'Cash') {
                $codAmount = $amountVal;
                $bankTransferAmount = 0.00;
            } else {
                $codAmount = 0.00;
                $bankTransferAmount = $amountVal;
            }
        } else {
            $codAmount = (float) ($codAmount ?? 0);
            $bankTransferAmount = (float) ($bankTransferAmount ?? 0);
            $amount = round($codAmount + $bankTransferAmount, 2);
            $paymentMode = ($codAmount > 0 && $bankTransferAmount <= 0) ? 'Cash' : 'Bank Transfer';
        }

        $this->merge([
            'cod_amount'           => $codAmount,
            'bank_transfer_amount' => $bankTransferAmount,
            'amount'               => $amount,
            'payment_mode'         => $paymentMode,
        ]);
    }

    public function rules(): array
    {
        return [
            'customer_id'          => [
                'required',
                \Illuminate\Validation\Rule::exists('customers', 'id')->where(fn($q) => (app(\App\Services\Tenancy\TenantContext::class)->getTenantId() ?? auth()->user()?->current_tenant_id) ? $q->where('tenant_id', (app(\App\Services\Tenancy\TenantContext::class)->getTenantId() ?? auth()->user()?->current_tenant_id)) : $q),
            ],
            'cod_amount'           => 'required|numeric|min:0',
            'bank_transfer_amount' => 'required|numeric|min:0',
            'amount'               => [
                'required',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) {
                    $customer = \App\Models\Customer::find($this->input('customer_id'));
                    if ($customer && $value > $customer->balance) {
                        $fail("The payment amount cannot exceed the customer's pending balance of Rs " . number_format($customer->balance, 2) . ".");
                    }
                }
            ],
            'payment_mode'         => 'required|in:Cash,Bank Transfer',
            'payment_type'         => 'required|in:Full,Part,Advance,Regular,Adjustment,Opening',
            'date'                 => 'required|date|before_or_equal:today',
            'notes'                => 'nullable|string|max:500',
        ];
    }
}
