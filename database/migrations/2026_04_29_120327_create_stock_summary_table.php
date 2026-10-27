<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_summary', function (Blueprint $table) {
            $table->id();
            $table->string('item_name')->unique();
            $table->string('unit')->default('kg');
            $table->decimal('current_stock', 10, 3)->default(0);
            $table->timestamp('last_updated')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_summary');
    }
};
