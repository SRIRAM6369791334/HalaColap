<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            $table->enum('payment_mode', ['Cash', 'UPI', 'NEFT', 'Cheque', 'Bank Transfer'])->change();
            $table->enum('payment_type', ['Full', 'Part', 'Advance', 'Regular', 'Adjustment', 'Opening'])->change();
        });
    }

    public function down(): void
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            $table->enum('payment_mode', ['Cash', 'UPI', 'NEFT', 'Cheque'])->change();
            $table->enum('payment_type', ['Full', 'Part', 'Advance'])->change();
        });
    }
};
