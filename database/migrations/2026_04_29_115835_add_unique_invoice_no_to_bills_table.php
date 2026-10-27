<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_bills', function (Blueprint $table) {
            if (!Schema::hasColumn('daily_bills', 'invoice_no')) {
                $table->string('invoice_no')->unique()->nullable()->after('id');
            } else {
                $table->unique('invoice_no');
            }
        });

        Schema::table('weekly_bills', function (Blueprint $table) {
            if (!Schema::hasColumn('weekly_bills', 'invoice_no')) {
                $table->string('invoice_no')->unique()->nullable()->after('id');
            } else {
                $table->unique('invoice_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('daily_bills', function (Blueprint $table) {
            $table->dropUnique(['invoice_no']);
        });

        Schema::table('weekly_bills', function (Blueprint $table) {
            $table->dropUnique(['invoice_no']);
        });
    }
};
