<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class CapitalTransaction extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'type',
        'date',
        'amount',
        'payment_mode',
        'bank_transfer_type',
        'person_name',
        'reference_number',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'date'   => 'date',
        'amount' => 'decimal:2',
    ];

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function getCurrentBalance(): float
    {
        $totalInvested = (float) static::where('type', 'Investment')->sum('amount');
        $totalTransferredToBusiness = (float) static::whereIn('type', ['Transfer to Cash', 'Transfer to Bank'])->sum('amount');
        $totalTransferredFromBusiness = (float) static::whereIn('type', ['Transfer from Cash', 'Transfer from Bank'])->sum('amount');
        $totalWithdrawn = (float) static::where('type', 'Withdrawal')->sum('amount');
        $totalVendorAdvanceFunded = (float) static::where('type', 'Vendor Advance Outflow')->sum('amount');

        return round(
            $totalInvested + $totalTransferredFromBusiness - $totalTransferredToBusiness - $totalWithdrawn - $totalVendorAdvanceFunded,
            2
        );
    }
}
