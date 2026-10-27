<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('color')->default('#6B7280');
            $table->timestamps();
        });

        // Seed default categories
        DB::table('expense_categories')->insert([
            ['name' => 'EMI',        'color' => '#EF4444'],
            ['name' => 'Salary',     'color' => '#3B82F6'],
            ['name' => 'Fuel',       'color' => '#F59E0B'],
            ['name' => 'Transport',  'color' => '#8B5CF6'],
            ['name' => 'Utilities',  'color' => '#10B981'],
            ['name' => 'Miscellaneous', 'color' => '#6B7280'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_categories');
    }
};
