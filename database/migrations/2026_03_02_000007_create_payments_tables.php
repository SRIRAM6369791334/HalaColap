<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customer_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->decimal('amount', 12, 2);
            $table->enum('payment_mode', ['Cash', 'UPI', 'NEFT', 'Cheque']);
            $table->enum('payment_type', ['Full', 'Part', 'Advance']);
            $table->decimal('balance_after', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('customer_id');
            $table->index('date');
        });

        Schema::create('dealer_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dealer_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->decimal('amount', 12, 2);
            $table->enum('payment_mode', ['Cash', 'UPI', 'NEFT', 'Cheque']);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('dealer_id');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dealer_payments');
        Schema::dropIfExists('customer_payments');
    }
};
