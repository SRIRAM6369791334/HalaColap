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
        Schema::table('day_load_batches', function (Blueprint $table) {
            $table->dropUnique('day_load_batches_billing_date_unique');
            $table->index('billing_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('day_load_batches', function (Blueprint $table) {
            $table->dropIndex(['billing_date']);
            $table->unique('billing_date');
        });
    }
};
