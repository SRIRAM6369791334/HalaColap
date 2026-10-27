<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('category'); // Using string for flexibility and SQLite compatibility
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->timestamps();
            $table->softDeletes();
            $table->index('date');
            $table->index('category');
        });

        Schema::create('emis', function (Blueprint $table) {
            $table->id();
            $table->string('loan_name'); // Renamed from item to match factory/test
            $table->string('bank_name')->nullable();
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->string('status')->default('Upcoming'); // Using string for SQLite compatibility
            $table->timestamps();
            $table->index('due_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emis');
        Schema::dropIfExists('expenses');
    }
};
