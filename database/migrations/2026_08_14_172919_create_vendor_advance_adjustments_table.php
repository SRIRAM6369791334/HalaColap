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
        Schema::create('vendor_advance_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_advance_id')->constrained('vendor_advances')->onDelete('cascade');
            $table->foreignId('day_load_entry_id')->constrained('day_load_entries')->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->date('date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('vendor_advance_id');
            $table->index('day_load_entry_id');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_advance_adjustments');
    }
};
