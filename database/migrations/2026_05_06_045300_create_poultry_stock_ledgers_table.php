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
        Schema::create('stock_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained();
            $table->foreignId('batch_id')->nullable()->constrained();
            $table->foreignId('warehouse_id')->nullable()->constrained();
            $table->decimal('quantity', 15, 3);
            $table->enum('type', ['IN', 'OUT']);
            $table->string('source_type'); // Purchase, Consumption, Mortality, Sale, Adjustment
            $table->unsignedBigInteger('source_id'); // ID of the purchase_item or consumption log
            $table->string('unit');
            $table->date('transaction_date');
            $table->text('remarks')->nullable();
            $table->timestamps();
            
            $table->index(['source_type', 'source_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_ledgers');
    }
};
