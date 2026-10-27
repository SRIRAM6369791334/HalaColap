<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_bank_ledgers', function (Blueprint $table) {
            $table->decimal('bank_expense', 12, 2)->default(0.00)->after('cash_expense');
        });
    }

    public function down(): void
    {
        Schema::table('cash_bank_ledgers', function (Blueprint $table) {
            $table->dropColumn('bank_expense');
        });
    }
};
