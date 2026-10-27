<?php

namespace App\Services;

use App\Models\CustomerPayment;
use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class CustomerPaymentService
{
    public function paginated(?string $query, int $perPage = 15, ?string $period = null, ?string $date = null): LengthAwarePaginator
    {
        return CustomerPayment::with('customer')
            ->search($query)
            ->when($period && $period !== 'all', fn (Builder $builder) => $this->applyPeriodFilter($builder, $period, $date))
            ->latest('date')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function record(array $data): CustomerPayment
    {
        $data['cod_amount'] = $data['cod_amount'] ?? 0;
        $data['bank_transfer_amount'] = $data['bank_transfer_amount'] ?? 0;
        $data['amount'] = round((float) $data['cod_amount'] + (float) $data['bank_transfer_amount'], 2);

        $payment = CustomerPayment::create($data);

        // Update customer balance
        $customer = Customer::findOrFail($data['customer_id']);
        $delta = match($data['payment_type']) {
            'Advance' => -abs($data['amount']),
            default   => -abs($data['amount']),
        };
        $customer->decrement('balance', abs($data['amount']));

        $payment->balance_after = $customer->fresh()->balance;
        $payment->save();

        // Recalculate cash/bank ledger
        app(CashBankLedgerService::class)->recalculateForDate(\Carbon\Carbon::parse($payment->date));

        return $payment;
    }

    public function allForExport(): \Illuminate\Database\Eloquent\Collection
    {
        return CustomerPayment::with('customer')->orderByDesc('date')->get();
    }

    private function applyPeriodFilter(Builder $query, string $period, ?string $date): Builder
    {
        $anchor = $date ? \Carbon\Carbon::parse($date) : today();

        return match ($period) {
            'daily' => $query->whereDate('date', $anchor),
            'weekly' => $query->whereBetween('date', [
                $anchor->copy()->startOfWeek()->toDateString(),
                $anchor->copy()->endOfWeek()->toDateString(),
            ]),
            'monthly' => $query
                ->whereYear('date', $anchor->year)
                ->whereMonth('date', $anchor->month),
            default => $query,
        };
    }
}
