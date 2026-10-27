<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poultry_mortalities', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('batch_id')->constrained('batches')->onDelete('cascade');
            $table->integer('count');
            $table->string('reason')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['date', 'batch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poultry_mortalities');
    }
};
