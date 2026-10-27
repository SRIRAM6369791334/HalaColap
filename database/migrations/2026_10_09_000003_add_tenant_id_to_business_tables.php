<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The 39 business tables to partition by tenant_id.
     *
     * @var array<string>
     */
    protected array $businessTables = [
        'activity_logs',
        'batches',
        'bird_batches',
        'capital_transactions',
        'cash_bank_ledgers',
        'customers',
        'customer_payments',
        'daily_bills',
        'daily_bill_items',
        'day_load_batches',
        'day_load_entries',
        'day_load_invoices',
        'dealers',
        'dealer_payments',
        'dealer_purchases',
        'dealer_purchase_items',
        'drivers',
        'emis',
        'entry_adjustment_logs',
        'expenses',
        'expense_categories',
        'items',
        'poultry_consumptions',
        'poultry_mortalities',
        'payment_adjustment_logs',
        'purchases',
        'purchase_items',
        'routes',
        'stock_items',
        'stock_ledgers',
        'stock_transactions',
        'vehicles',
        'vendor_advances',
        'vendor_advance_adjustments',
        'vendor_payments',
        'vendors',
        'warehouses',
        'weekly_bills',
        'weekly_bill_items',
    ];

    /**
     * Run the migrations.
     *
     * 3-Stage Partitioning:
     * Stage 1: Add nullable tenant_id to all 39 business tables.
     * Stage 2: Backfill existing rows with tenant_id = 1 (Tenant #1 MSR Broilers).
     * Stage 3: Alter tenant_id to NOT NULL with index and foreign key referencing tenants(id) ON DELETE CASCADE.
     */
    public function up(): void
    {
        $isSqlite = DB::getDriverName() === 'sqlite';

        // Stage 1: Add nullable tenant_id to all 39 business tables
        foreach ($this->businessTables as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'tenant_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                });
            }
        }

        // Stage 2: Ensure Tenant #1 exists and backfill existing rows
        if (Schema::hasTable('tenants')) {
            DB::table('tenants')->insertOrIgnore([
                'id' => 1,
                'name' => 'MSR Broilers',
                'slug' => 'msr-broilers',
                'company_name' => 'MSR Broilers',
                'email' => 'admin@poultry.com',
                'currency' => 'INR',
                'timezone' => 'Asia/Kolkata',
                'date_format' => 'd-m-Y',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ($this->businessTables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'tenant_id')) {
                DB::table($tableName)
                    ->whereNull('tenant_id')
                    ->update(['tenant_id' => 1]);
            }
        }

        // Stage 3: Alter tenant_id to NOT NULL with default 1, index, and foreign key constraint
        foreach ($this->businessTables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'tenant_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName, $isSqlite) {
                    // Alter column to NOT NULL with default 1 for backward compatibility
                    $table->unsignedBigInteger('tenant_id')->default(1)->nullable(false)->change();

                    // Add index on tenant_id if not already present
                    $indexName = "{$tableName}_tenant_id_index";
                    if (! Schema::hasIndex($tableName, $indexName)) {
                        $table->index('tenant_id', $indexName);
                    }

                    // Add foreign key constraint to tenants(id) ON DELETE CASCADE
                    if (Schema::hasTable('tenants')) {
                        $table->foreign('tenant_id')
                            ->references('id')
                            ->on('tenants')
                            ->onDelete('cascade');
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * Drops foreign keys, indexes, and tenant_id column across all 39 business tables.
     */
    public function down(): void
    {
        $isSqlite = DB::getDriverName() === 'sqlite';

        foreach ($this->businessTables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'tenant_id')) {
                $indexName = "{$tableName}_tenant_id_index";

                if ($isSqlite) {
                    // SQLite requires dropping the index before dropping the column
                    try {
                        Schema::table($tableName, function (Blueprint $table) use ($tableName, $indexName) {
                            if (Schema::hasIndex($tableName, $indexName)) {
                                $table->dropIndex($indexName);
                            }
                        });
                    } catch (\Throwable $e) {}

                    Schema::table($tableName, function (Blueprint $table) {
                        try {
                            $table->dropForeign(['tenant_id']);
                        } catch (\Throwable $e) {}

                        $table->dropColumn('tenant_id');
                    });
                } else {
                    Schema::table($tableName, function (Blueprint $table) use ($tableName, $indexName) {
                        try {
                            $table->dropForeign(['tenant_id']);
                        } catch (\Throwable $e) {}

                        try {
                            if (Schema::hasIndex($tableName, $indexName)) {
                                $table->dropIndex($indexName);
                            }
                        } catch (\Throwable $e) {}

                        $table->dropColumn('tenant_id');
                    });
                }
            }
        }
    }
};
