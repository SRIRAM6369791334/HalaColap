<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('day_load_entries', function (Blueprint $table) {
            $table->decimal('amount', 12, 2)->default(0.00)->after('customer_rate');
        });
    }

    public function down(): void
    {
        Schema::table('day_load_entries', function (Blueprint $table) {
            $table->dropColumn('amount');
        });
    }
};
