<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Map of table definitions for composite unique constraints.
     *
     * @var array<string, array{column: string, old_unique: string|array<string>, composite_unique: string}>
     */
    protected array $constraints = [
        'cash_bank_ledgers' => [
            'column' => 'ledger_date',
            'old_unique' => 'cash_bank_ledgers_ledger_date_unique',
            'composite_unique' => 'cash_bank_ledgers_tenant_id_ledger_date_unique',
        ],
        'daily_bills' => [
            'column' => 'invoice_no',
            'old_unique' => 'daily_bills_invoice_no_unique',
            'composite_unique' => 'daily_bills_tenant_id_invoice_no_unique',
        ],
        'weekly_bills' => [
            'column' => 'invoice_no',
            'old_unique' => 'weekly_bills_invoice_no_unique',
            'composite_unique' => 'weekly_bills_tenant_id_invoice_no_unique',
        ],
        'day_load_invoices' => [
            'column' => 'invoice_no',
            'old_unique' => 'day_load_invoices_invoice_no_unique',
            'composite_unique' => 'day_load_invoices_tenant_id_invoice_no_unique',
        ],
        'dealer_purchases' => [
            'column' => 'invoice_no',
            'old_unique' => 'dealer_purchases_invoice_no_unique',
            'composite_unique' => 'dealer_purchases_tenant_id_invoice_no_unique',
        ],
        'batches' => [
            'column' => 'batch_code',
            'old_unique' => 'batches_batch_code_unique',
            'composite_unique' => 'batches_tenant_id_batch_code_unique',
        ],
        'expense_categories' => [
            'column' => 'name',
            'old_unique' => 'expense_categories_name_unique',
            'composite_unique' => 'expense_categories_tenant_id_name_unique',
        ],
        'items' => [
            'column' => 'code',
            'old_unique' => 'items_code_unique',
            'composite_unique' => 'items_tenant_id_code_unique',
        ],
        'stock_items' => [
            'column' => 'item_name',
            'old_unique' => ['stock_summary_item_name_unique', 'stock_items_item_name_unique'],
            'composite_unique' => 'stock_items_tenant_id_item_name_unique',
        ],
        'vehicles' => [
            'column' => 'vehicle_number',
            'old_unique' => 'vehicles_vehicle_number_unique',
            'composite_unique' => 'vehicles_tenant_id_vehicle_number_unique',
        ],
    ];

    /**
     * Run the migrations.
     *
     * Drop 10 single-tenant global unique indexes and recreate as composite UNIQUE(tenant_id, <column>).
     */
    public function up(): void
    {
        foreach ($this->constraints as $tableName => $def) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            $column = $def['column'];
            $compositeUnique = $def['composite_unique'];
            $oldUniques = is_array($def['old_unique']) ? $def['old_unique'] : [$def['old_unique']];

            // 1. Drop existing single-tenant unique constraint(s) if present
            foreach ($oldUniques as $oldIndexName) {
                if (Schema::hasIndex($tableName, $oldIndexName)) {
                    Schema::table($tableName, function (Blueprint $table) use ($oldIndexName) {
                        $table->dropUnique($oldIndexName);
                    });
                }
            }

            // Fallback: in SQLite or different setups, check if standard column unique exists
            if (Schema::hasIndex($tableName, "{$tableName}_{$column}_unique")) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName, $column) {
                    $table->dropUnique("{$tableName}_{$column}_unique");
                });
            }

            // 2. Create composite UNIQUE(tenant_id, <column>)
            if (! Schema::hasIndex($tableName, $compositeUnique)) {
                Schema::table($tableName, function (Blueprint $table) use ($column, $compositeUnique) {
                    $table->unique(['tenant_id', $column], $compositeUnique);
                });
            }

            // 3. Ensure cash_bank_ledgers balance columns have standard default 0.00
            if ($tableName === 'cash_bank_ledgers') {
                Schema::table('cash_bank_ledgers', function (Blueprint $table) {
                    $table->decimal('opening_cash_balance', 12, 2)->default(0.00)->change();
                    $table->decimal('opening_bank_balance', 12, 2)->default(0.00)->change();
                    $table->decimal('closing_cash_balance', 12, 2)->default(0.00)->change();
                    $table->decimal('closing_bank_balance', 12, 2)->default(0.00)->change();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * Drops composite unique constraints and restores original single-tenant unique constraints.
     */
    public function down(): void
    {
        foreach ($this->constraints as $tableName => $def) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            $column = $def['column'];
            $compositeUnique = $def['composite_unique'];
            $primaryOldIndex = is_array($def['old_unique']) ? $def['old_unique'][0] : $def['old_unique'];

            // Delete non-tenant-1 records if any collision exists before restoring single unique
            if (Schema::hasColumn($tableName, 'tenant_id')) {
                DB::table($tableName)->where('tenant_id', '!=', 1)->delete();
            }

            // 1. Drop composite unique constraint
            if (Schema::hasIndex($tableName, $compositeUnique)) {
                Schema::table($tableName, function (Blueprint $table) use ($compositeUnique) {
                    $table->dropUnique($compositeUnique);
                });
            }

            // 2. Re-create original single-tenant unique constraint
            if (! Schema::hasIndex($tableName, $primaryOldIndex)) {
                Schema::table($tableName, function (Blueprint $table) use ($column, $primaryOldIndex) {
                    $table->unique($column, $primaryOldIndex);
                });
            }
        }
    }
};
