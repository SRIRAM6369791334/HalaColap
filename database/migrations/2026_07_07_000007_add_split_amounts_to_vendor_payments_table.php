<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vendor_payments', function (Blueprint $table) {
            $table->decimal('cash_amount', 12, 2)->default(0.00)->after('amount');
            $table->decimal('bank_amount', 12, 2)->default(0.00)->after('cash_amount');
            $table->decimal('total_amount', 12, 2)
                ->storedAs('`cash_amount` + `bank_amount`')
                ->after('bank_amount');
            $table->enum('bank_transfer_type', ['UPI', 'Bank Transfer', 'NEFT', 'RTGS', 'IMPS', 'Cheque', 'Other'])
                ->nullable()
                ->after('payment_mode');
        });

        // Backfill existing rows
        DB::table('vendor_payments')
            ->where('payment_mode', 'Cash')
            ->update([
                'cash_amount' => DB::raw('`amount`'),
                'bank_amount' => 0.00,
            ]);

        DB::table('vendor_payments')
            ->whereIn('payment_mode', ['UPI', 'Cheque', 'NEFT', 'Bank Transfer'])
            ->update([
                'cash_amount' => 0.00,
                'bank_amount' => DB::raw('`amount`'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_payments', function (Blueprint $table) {
            $table->dropColumn(['bank_transfer_type', 'total_amount', 'bank_amount', 'cash_amount']);
        });
    }
};
