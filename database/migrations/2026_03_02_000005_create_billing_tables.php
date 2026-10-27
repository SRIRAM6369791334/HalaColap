<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('weekly_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('items_description')->nullable();
            $table->decimal('quantity_kg', 10, 2)->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('Pending');
            $table->timestamps();
            $table->index('status');
            $table->index('period_start');
        });

        Schema::create('daily_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->string('items_description')->nullable();
            $table->decimal('quantity_kg', 10, 2)->nullable();
            $table->decimal('rate_per_kg', 8, 2)->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('Pending');
            $table->timestamps();
            $table->index('date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_bills');
        Schema::dropIfExists('weekly_bills');
    }
};
