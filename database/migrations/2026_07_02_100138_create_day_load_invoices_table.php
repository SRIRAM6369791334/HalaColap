<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('day_load_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('day_load_batches')->cascadeOnDelete();
            $table->string('invoice_no')->unique();
            $table->date('invoice_date');
            $table->integer('total_boxes')->default(0);
            $table->decimal('total_box_weight', 10, 2)->default(0.00);
            $table->decimal('total_empty_weight', 10, 2)->default(0.00);
            $table->decimal('total_bird_weight', 10, 2)->default(0.00);
            $table->decimal('total_farm_weight', 10, 2)->default(0.00);
            $table->decimal('total_loss_weight', 10, 2)->default(0.00);
            $table->enum('status', ['Draft', 'Final'])->default('Draft');
            $table->integer('version')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('day_load_invoices');
    }
};
