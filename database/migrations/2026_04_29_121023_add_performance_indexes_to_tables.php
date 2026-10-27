<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_bills', function (Blueprint $table) {
            $table->index(['date', 'customer_id']);
        });

        Schema::table('weekly_bills', function (Blueprint $table) {
            $table->index(['period_start', 'customer_id']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->index(['date', 'vendor_id']);
        });

        Schema::table('customer_payments', function (Blueprint $table) {
            $table->index(['date', 'customer_id']);
        });

        // expenses table already has indexes for date and category based on its migration file
    }

    public function down(): void
    {
        Schema::table('daily_bills', function (Blueprint $table) {
            $table->dropIndex(['date', 'customer_id']);
        });

        Schema::table('weekly_bills', function (Blueprint $table) {
            $table->dropIndex(['period_start', 'customer_id']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex(['date', 'vendor_id']);
        });

        Schema::table('customer_payments', function (Blueprint $table) {
            $table->dropIndex(['date', 'customer_id']);
        });
    }
};
