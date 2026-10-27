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
        Schema::table('stock_ledgers', function (Blueprint $table) {
            // Composite index for fast stock checks and covering index-only aggregation scans
            $table->index(['item_id', 'type', 'quantity'], 'idx_ledgers_item_type_qty');
            
            // Secondary index for date sorting/filtering (physical transaction date)
            $table->index('transaction_date', 'idx_ledgers_txn_date');

            // Secondary index for creation audit sorting/filtering
            $table->index('created_at', 'idx_ledgers_created_at');
        });

        Schema::table('batches', function (Blueprint $table) {
            // Index for active/closed batch lookups
            $table->index('status', 'idx_batches_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes safely and crash-proofly by using separate Schema::table calls
        $this->dropIndexIfExists('stock_ledgers', 'idx_ledgers_item_type_qty');
        $this->dropIndexIfExists('stock_ledgers', 'idx_ledgers_txn_date');
        $this->dropIndexIfExists('stock_ledgers', 'idx_ledgers_created_at');

        $this->dropIndexIfExists('batches', 'idx_batches_status');
    }

    /**
     * Helper to safely drop index only if it exists.
     */
    private function dropIndexIfExists(string $tableName, string $indexName): void
    {
        try {
            Schema::table($tableName, function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        } catch (\Exception $e) {
            // Silently ignore if index does not exist
        }
    }
};



