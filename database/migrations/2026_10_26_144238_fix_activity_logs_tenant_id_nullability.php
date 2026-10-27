<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('activity_logs') && Schema::hasColumn('activity_logs', 'tenant_id')) {
            DB::table('activity_logs')->whereNull('tenant_id')->update(['tenant_id' => 1]);

            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE activity_logs MODIFY tenant_id BIGINT UNSIGNED NOT NULL DEFAULT 1');
            } else {
                Schema::table('activity_logs', function (Blueprint $table) {
                    $table->unsignedBigInteger('tenant_id')->default(1)->nullable(false)->change();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('activity_logs') && Schema::hasColumn('activity_logs', 'tenant_id')) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE activity_logs MODIFY tenant_id BIGINT UNSIGNED NULL DEFAULT 1');
            } else {
                Schema::table('activity_logs', function (Blueprint $table) {
                    $table->unsignedBigInteger('tenant_id')->default(1)->nullable()->change();
                });
            }
        }
    }
};
