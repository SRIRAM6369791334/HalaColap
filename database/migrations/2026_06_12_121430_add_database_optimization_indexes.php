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
        Schema::table('items', function (Blueprint $table) {
            $table->index('name');
            $table->index('type');
            $table->index('category');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->index('invoice_no');
            $table->index('payment_mode');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->index('item_name');
        });

        Schema::table('daily_bill_items', function (Blueprint $table) {
            $table->index('item_name');
        });

        Schema::table('weekly_bill_items', function (Blueprint $table) {
            $table->index('item_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['type']);
            $table->dropIndex(['category']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex(['invoice_no']);
            $table->dropIndex(['payment_mode']);
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropIndex(['item_name']);
        });

        Schema::table('daily_bill_items', function (Blueprint $table) {
            $table->dropIndex(['item_name']);
        });

        Schema::table('weekly_bill_items', function (Blueprint $table) {
            $table->dropIndex(['item_name']);
        });
    }
};
