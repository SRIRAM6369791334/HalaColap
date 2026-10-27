<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('day_load_batches', function (Blueprint $table) {
            $table->foreign('invoice_id')
                ->references('id')
                ->on('day_load_invoices')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('day_load_batches', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
        });
    }
};
