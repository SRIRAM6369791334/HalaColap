<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_bills', function (Blueprint $table) {
            if (!Schema::hasColumn('daily_bills', 'date_from')) {
                $table->date('date_from')->nullable()->after('date');
            }
            if (!Schema::hasColumn('daily_bills', 'date_to')) {
                $table->date('date_to')->nullable()->after('date_from');
            }
        });
    }

    public function down(): void
    {
        Schema::table('daily_bills', function (Blueprint $table) {
            $columnsToDrop = array_filter([
                Schema::hasColumn('daily_bills', 'date_from') ? 'date_from' : null,
                Schema::hasColumn('daily_bills', 'date_to') ? 'date_to' : null,
            ]);
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
