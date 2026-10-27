<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('day_load_batches', function (Blueprint $table) {
            $table->id();
            $table->date('billing_date')->unique();
            $table->enum('status', ['Open', 'Invoiced', 'Locked'])->default('Open');
            $table->integer('total_boxes')->default(0);
            $table->decimal('total_box_weight', 10, 2)->default(0.00);
            $table->decimal('total_empty_weight', 10, 2)->default(0.00);
            $table->decimal('total_bird_weight', 10, 2)->default(0.00);
            $table->decimal('total_farm_weight', 10, 2)->default(0.00);
            $table->decimal('total_loss_weight', 10, 2)->default(0.00);
            $table->foreignId('invoice_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('day_load_batches');
    }
};
