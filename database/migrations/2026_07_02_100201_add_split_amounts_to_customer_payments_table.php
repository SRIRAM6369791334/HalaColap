<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            $table->decimal('cod_amount', 12, 2)->default(0.00)->after('amount');
            $table->decimal('bank_transfer_amount', 12, 2)->default(0.00)->after('cod_amount');
            $table->decimal('total_amount', 12, 2)
                ->storedAs('`cod_amount` + `bank_transfer_amount`')
                ->after('bank_transfer_amount');
        });

        DB::table('customer_payments')
            ->where('payment_mode', 'Cash')
            ->update([
                'cod_amount' => DB::raw('`amount`'),
                'bank_transfer_amount' => 0,
            ]);

        DB::table('customer_payments')
            ->whereIn('payment_mode', ['UPI', 'Cheque', 'NEFT', 'Bank Transfer'])
            ->update([
                'cod_amount' => 0,
                'bank_transfer_amount' => DB::raw('`amount`'),
            ]);
    }

    public function down(): void
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            $table->dropColumn('total_amount');
        });

        Schema::table('customer_payments', function (Blueprint $table) {
            $table->dropColumn(['bank_transfer_amount', 'cod_amount']);
        });
    }
};
