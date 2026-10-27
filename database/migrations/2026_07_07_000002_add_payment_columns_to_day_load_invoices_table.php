<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('day_load_invoices', function (Blueprint $table) {
            $table->decimal('total_amount', 12, 2)->default(0.00)->after('total_loss_weight');
            $table->decimal('amount_paid', 12, 2)->default(0.00)->after('total_amount');
            $table->decimal('balance_due', 12, 2)->storedAs('`total_amount` - `amount_paid`')->after('amount_paid');
            $table->enum('payment_status', ['Pending', 'Partial', 'Paid'])->default('Pending')->after('balance_due');
        });
    }

    public function down(): void
    {
        Schema::table('day_load_invoices', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'balance_due', 'amount_paid', 'total_amount']);
        });
    }
};
