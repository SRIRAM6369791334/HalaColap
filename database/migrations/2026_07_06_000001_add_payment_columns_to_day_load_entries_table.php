<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('day_load_entries', function (Blueprint $table) {
            $table->decimal('dealer_collected', 12, 2)->default(0.00)->after('remarks');
            $table->decimal('vendor_paid', 12, 2)->default(0.00)->after('dealer_collected');
            $table->string('dealer_payment_status')->default('Pending')->after('vendor_paid');
            $table->string('vendor_payment_status')->default('Pending')->after('dealer_payment_status');
        });

        Schema::table('day_load_batches', function (Blueprint $table) {
            $table->decimal('total_dealer_income', 14, 2)->default(0.00)->after('total_weight');
            $table->decimal('total_vendor_cost', 14, 2)->default(0.00)->after('total_dealer_income');
            $table->decimal('total_dealer_collected', 14, 2)->default(0.00)->after('total_vendor_cost');
            $table->decimal('total_vendor_paid', 14, 2)->default(0.00)->after('total_dealer_collected');
        });
    }

    public function down(): void
    {
        Schema::table('day_load_entries', function (Blueprint $table) {
            $table->dropColumn(['dealer_collected', 'vendor_paid', 'dealer_payment_status', 'vendor_payment_status']);
        });

        Schema::table('day_load_batches', function (Blueprint $table) {
            $table->dropColumn(['total_dealer_income', 'total_vendor_cost', 'total_dealer_collected', 'total_vendor_paid']);
        });
    }
};
