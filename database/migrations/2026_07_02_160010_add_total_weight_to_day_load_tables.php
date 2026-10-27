<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('day_load_entries', function (Blueprint $table) {
            $table->decimal('total_weight', 10, 2)->nullable()->after('farm_weight');
        });

        Schema::table('day_load_batches', function (Blueprint $table) {
            $table->decimal('total_weight', 10, 2)->default(0.00)->after('total_farm_weight');
        });

        Schema::table('day_load_invoices', function (Blueprint $table) {
            $table->decimal('total_weight', 10, 2)->default(0.00)->after('total_farm_weight');
        });
    }

    public function down(): void
    {
        Schema::table('day_load_entries', function (Blueprint $table) {
            $table->dropColumn('total_weight');
        });

        Schema::table('day_load_batches', function (Blueprint $table) {
            $table->dropColumn('total_weight');
        });

        Schema::table('day_load_invoices', function (Blueprint $table) {
            $table->dropColumn('total_weight');
        });
    }
};
