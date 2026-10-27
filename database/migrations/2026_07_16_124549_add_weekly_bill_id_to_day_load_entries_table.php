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
        Schema::table('day_load_entries', function (Blueprint $table) {
            $table->foreignId('weekly_bill_id')->nullable()->after('dealer_id')->constrained('weekly_bills')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('day_load_entries', function (Blueprint $table) {
            $table->dropForeign(['weekly_bill_id']);
            $table->dropColumn('weekly_bill_id');
        });
    }
};
