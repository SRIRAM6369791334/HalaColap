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
        Schema::create('capital_transactions', function (Blueprint $table) {
            $table->id();
            $table->enum('type', [
                'Investment',
                'Transfer to Cash',
                'Transfer to Bank',
                'Transfer from Cash',
                'Transfer from Bank',
                'Withdrawal',
                'Vendor Advance Outflow'
            ]);
            $table->date('date');
            $table->decimal('amount', 12, 2);
            $table->string('payment_mode')->default('Cash');
            $table->string('bank_transfer_type')->nullable();
            $table->string('person_name')->nullable();
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('date');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capital_transactions');
    }
};
