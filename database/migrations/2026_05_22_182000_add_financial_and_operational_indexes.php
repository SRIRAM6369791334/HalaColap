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
        Schema::table('customers', function (Blueprint $table) {
            $table->index('balance', 'idx_customers_balance');
        });

        Schema::table('dealers', function (Blueprint $table) {
            $table->index('pending_amount', 'idx_dealers_pending_amount');
        });

        Schema::table('weekly_bills', function (Blueprint $table) {
            $table->index(['period_end', 'customer_id'], 'idx_weekly_bills_period_customer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIndexIfExists('customers', 'idx_customers_balance');
        $this->dropIndexIfExists('dealers', 'idx_dealers_pending_amount');
        $this->dropIndexIfExists('weekly_bills', 'idx_weekly_bills_period_customer');
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
            // Silently ignore if index does not exist to prevent rollback locks
        }
    }
};
