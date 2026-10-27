<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['purchase_in', 'sale_out', 'adjustment']);
            $table->string('item_name');
            $table->decimal('quantity', 10, 3);
            $table->string('unit')->default('kg');
            $table->decimal('rate', 10, 2)->nullable();
            $table->string('reference_type')->nullable(); // e.g. App\Models\DailyBill
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->date('date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
