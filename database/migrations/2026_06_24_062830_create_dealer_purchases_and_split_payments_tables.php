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
        Schema::create('dealer_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dealer_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('invoice_no')->unique();
            $table->decimal('amount', 12, 2);
            $table->decimal('gst_percentage', 5, 2)->default(18);
            $table->decimal('gst_amount', 12, 2);
            $table->decimal('net_amount', 12, 2);
            $table->foreignId('weekly_bill_id')->nullable()->constrained('weekly_bills')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('dealer_purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dealer_purchase_id')->constrained()->cascadeOnDelete();
            $table->string('item_name');
            $table->decimal('quantity_kg', 10, 2);
            $table->decimal('rate_per_kg', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->timestamps();
        });

        Schema::table('weekly_bills', function (Blueprint $table) {
            $table->decimal('monday_payment_amount', 12, 2)->nullable();
            $table->string('monday_payment_status')->default('Pending');
            $table->decimal('friday_payment_amount', 12, 2)->nullable();
            $table->string('friday_payment_status')->default('Pending');
            $table->decimal('previous_outstanding', 12, 2)->default(0.00);
            $table->decimal('payments_during_week', 12, 2)->default(0.00);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weekly_bills', function (Blueprint $table) {
            $table->dropColumn([
                'monday_payment_amount',
                'monday_payment_status',
                'friday_payment_amount',
                'friday_payment_status',
                'previous_outstanding',
                'payments_during_week'
            ]);
        });

        Schema::dropIfExists('dealer_purchase_items');
        Schema::dropIfExists('dealer_purchases');
    }
};
