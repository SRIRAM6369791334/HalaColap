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
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_code')->unique();
            $table->date('placement_date');
            $table->integer('initial_count');
            $table->integer('current_count');
            $table->string('breed')->nullable();
            $table->decimal('avg_placement_weight', 8, 3)->nullable();
            $table->enum('status', ['Active', 'Closed'])->default('Active');
            $table->date('closed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
