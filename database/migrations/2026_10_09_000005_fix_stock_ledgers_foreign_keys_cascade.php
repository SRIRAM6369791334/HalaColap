<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fix foreign keys on stock_ledgers so that batch_id, item_id, and warehouse_id
     * cascade on delete when referenced batches, items, or warehouses are deleted,
     * preventing MySQL Error 1451 when a tenant and its entities are deleted.
     */
    public function up(): void
    {
        $isSqlite = DB::getDriverName() === 'sqlite';

        if ($isSqlite) {
            try {
                Schema::table('stock_ledgers', function (Blueprint $table) {
                    $table->dropForeign(['batch_id']);
                    $table->dropForeign(['item_id']);
                    $table->dropForeign(['warehouse_id']);

                    $table->foreign('batch_id')->references('id')->on('batches')->onDelete('cascade');
                    $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
                    $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
                });
            } catch (\Throwable $e) {
                // Graceful fallback for SQLite in-memory testing if foreign key dropping is restricted
            }
        } else {
            Schema::table('stock_ledgers', function (Blueprint $table) {
                $table->dropForeign(['batch_id']);
                $table->dropForeign(['item_id']);
                $table->dropForeign(['warehouse_id']);

                $table->foreign('batch_id')->references('id')->on('batches')->onDelete('cascade');
                $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
                $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * Restores original default/restrict foreign key constraints.
     */
    public function down(): void
    {
        $isSqlite = DB::getDriverName() === 'sqlite';

        if ($isSqlite) {
            try {
                Schema::table('stock_ledgers', function (Blueprint $table) {
                    $table->dropForeign(['batch_id']);
                    $table->dropForeign(['item_id']);
                    $table->dropForeign(['warehouse_id']);

                    $table->foreign('batch_id')->references('id')->on('batches')->onDelete('restrict');
                    $table->foreign('item_id')->references('id')->on('items')->onDelete('restrict');
                    $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('restrict');
                });
            } catch (\Throwable $e) {
                // Graceful fallback for SQLite in-memory testing
            }
        } else {
            Schema::table('stock_ledgers', function (Blueprint $table) {
                $table->dropForeign(['batch_id']);
                $table->dropForeign(['item_id']);
                $table->dropForeign(['warehouse_id']);

                $table->foreign('batch_id')->references('id')->on('batches')->onDelete('restrict');
                $table->foreign('item_id')->references('id')->on('items')->onDelete('restrict');
                $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('restrict');
            });
        }
    }
};
