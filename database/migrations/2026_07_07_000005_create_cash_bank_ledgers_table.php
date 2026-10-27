<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_bank_ledgers', function (Blueprint $table) {
            $table->id();
            $table->date('ledger_date')->unique();
            $table->decimal('opening_cash_balance', 12, 2);
            $table->decimal('opening_bank_balance', 12, 2);
            $table->decimal('cash_income', 12, 2)->default(0.00);
            $table->decimal('bank_income', 12, 2)->default(0.00);
            $table->decimal('cash_expense', 12, 2)->default(0.00);
            $table->decimal('closing_cash_balance', 12, 2);
            $table->decimal('closing_bank_balance', 12, 2);
            $table->boolean('is_approved')->default(false);
            $table->decimal('approved_amount', 12, 2)->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_bank_ledgers');
    }
};
