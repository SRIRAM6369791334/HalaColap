<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('day_load_entries', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->nullable()->after('dealer_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('day_load_entries', function (Blueprint $table) {
            $table->dropIndex(['sort_order']);
            $table->dropColumn('sort_order');
        });
    }
};
