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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['Feed', 'Chick', 'Medicine', 'Vaccine', 'Equipment', 'Other']);
            $table->string('category')->nullable(); // Starter, Grower, Finisher, etc.
            $table->string('brand')->nullable();
            $table->string('base_unit')->default('kg'); // kg, nos, bag, ml
            $table->decimal('conversion_rate', 10, 2)->default(1.00); // 1 Bag = 50kg
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
