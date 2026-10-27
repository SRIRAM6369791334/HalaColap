<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dealer_payments', function (Blueprint $table) {
            $table->string('payment_group_id', 36)
                ->nullable()
                ->index()
                ->after('day_load_entry_id');
        });
    }

    public function down(): void
    {
        Schema::table('dealer_payments', function (Blueprint $table) {
            $table->dropColumn('payment_group_id');
        });
    }
};
