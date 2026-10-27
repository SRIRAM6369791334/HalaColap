<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('day_load_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('day_load_batches')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->restrictOnDelete();
            $table->foreignId('dealer_id')->constrained('dealers')->restrictOnDelete();
            $table->integer('no_of_boxes');
            $table->decimal('box_weight', 10, 2);
            $table->decimal('empty_weight', 10, 2);
            $table->decimal('bird_weight', 10, 2);
            $table->decimal('farm_weight', 10, 2)->nullable();
            $table->decimal('loss_weight', 10, 2)->nullable();
            $table->enum('status', ['Active', 'Adjusted', 'Split', 'Cancelled'])->default('Active');
            $table->foreignId('parent_entry_id')->nullable()->constrained('day_load_entries')->nullOnDelete();
            $table->integer('version')->default(1);
            $table->string('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('day_load_entries');
    }
};
