<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entry_adjustment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')->constrained('day_load_entries')->cascadeOnDelete();
            $table->enum('action_type', ['Create', 'Edit', 'Split', 'Cancel']);
            $table->longText('old_values')->nullable();
            $table->longText('new_values')->nullable();
            $table->foreignId('resulting_entry_id')->nullable()->constrained('day_load_entries')->nullOnDelete();
            $table->string('reason')->nullable();
            $table->foreignId('adjusted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entry_adjustment_logs');
    }
};
