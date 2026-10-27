<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dealer_payments', function (Blueprint $table) {
            $table->decimal('cash_amount', 12, 2)->default(0.00)->after('amount');
            $table->decimal('bank_amount', 12, 2)->default(0.00)->after('cash_amount');
            $table->decimal('total_amount', 12, 2)
                ->storedAs('`cash_amount` + `bank_amount`')
                ->after('bank_amount');
            $table->enum('bank_transfer_type', ['UPI', 'Bank Transfer', 'NEFT', 'RTGS', 'IMPS', 'Cheque', 'Other'])
                ->nullable()
                ->after('payment_mode');
        });

        // Backfill existing rows: amount → cash_amount (for old Cash payments) or bank_amount (for old bank payments)
        DB::table('dealer_payments')
            ->where('payment_mode', 'Cash')
            ->update([
                'cash_amount' => DB::raw('`amount`'),
                'bank_amount' => 0,
            ]);

        DB::table('dealer_payments')
            ->whereIn('payment_mode', [
                'UPI', 'GPay', 'PhonePe', 'Paytm',
                'Cheque', 'NEFT', 'Bank Transfer', 'IMPS', 'RTGS',
            ])
            ->update([
                'cash_amount' => 0,
                'bank_amount' => DB::raw('`amount`'),
            ]);

        // Credit and Adjustment are NOT cash/bank movements:
        // - Credit = money owed by dealer, not yet received → no real cash flow
        // - Adjustment = correction entry, not actual payment → excluded from both buckets
        // Both remain at cash_amount=0, bank_amount=0; the old `amount` column
        // preserves the original value for backward compatibility.
    }

    public function down(): void
    {
        Schema::table('dealer_payments', function (Blueprint $table) {
            $table->dropColumn(['bank_transfer_type', 'total_amount', 'bank_amount', 'cash_amount']);
        });
    }
};
