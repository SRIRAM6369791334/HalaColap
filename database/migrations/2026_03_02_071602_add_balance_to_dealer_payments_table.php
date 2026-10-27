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
        Schema::table('dealer_payments', function (Blueprint $table) {
            $table->decimal('pending_balance_after', 12, 2)->default(0)->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('dealer_payments', function (Blueprint $table) {
            $table->dropColumn('pending_balance_after');
        });
    }
};
