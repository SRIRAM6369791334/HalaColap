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
        Schema::table('daily_bills', function (Blueprint $table) {
            if (Schema::hasColumn('daily_bills', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->change();
            }
            if (!Schema::hasColumn('daily_bills', 'dealer_id')) {
                $table->foreignId('dealer_id')->nullable()->after('customer_id')->constrained('dealers')->onDelete('cascade');
            }
            if (!Schema::hasColumn('daily_bills', 'discount_percentage')) {
                $table->decimal('discount_percentage', 5, 2)->default(0.00)->after('net_amount');
            }
            if (!Schema::hasColumn('daily_bills', 'discount_amount')) {
                $table->decimal('discount_amount', 12, 2)->default(0.00)->after('discount_percentage');
            }
            if (!Schema::hasColumn('daily_bills', 'previous_outstanding')) {
                $table->decimal('previous_outstanding', 12, 2)->default(0.00)->after('bank_method');
            }
            if (!Schema::hasColumn('daily_bills', 'payments_during_day')) {
                $table->decimal('payments_during_day', 12, 2)->default(0.00)->after('previous_outstanding');
            }
        });

        Schema::table('day_load_entries', function (Blueprint $table) {
            if (!Schema::hasColumn('day_load_entries', 'daily_bill_id')) {
                $table->foreignId('daily_bill_id')->nullable()->after('weekly_bill_id')->constrained('daily_bills')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('day_load_entries', function (Blueprint $table) {
            if (Schema::hasColumn('day_load_entries', 'daily_bill_id')) {
                $table->dropForeign(['daily_bill_id']);
                $table->dropColumn('daily_bill_id');
            }
        });

        Schema::table('daily_bills', function (Blueprint $table) {
            if (Schema::hasColumn('daily_bills', 'dealer_id')) {
                $table->dropForeign(['dealer_id']);
                $table->dropColumn(['dealer_id', 'discount_percentage', 'discount_amount', 'previous_outstanding', 'payments_during_day']);
            }
        });
    }
};
