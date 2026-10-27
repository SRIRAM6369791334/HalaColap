<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->assertExpenseCategoriesCanBeMapped();

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('category_id')
                ->nullable()
                ->after('category')
                ->constrained('expense_categories')
                ->nullOnDelete();
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("
                UPDATE `expenses` e
                INNER JOIN `expense_categories` ec
                    ON LOWER(TRIM(e.`category`)) = LOWER(TRIM(ec.`name`))
                SET e.`category_id` = ec.`id`
            ");
        }
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }

    private function assertExpenseCategoriesCanBeMapped(): void
    {
        $duplicateCategories = DB::select("
            SELECT LOWER(TRIM(`name`)) AS category_name, COUNT(*) AS count
            FROM `expense_categories`
            GROUP BY LOWER(TRIM(`name`))
            HAVING COUNT(*) > 1
            ORDER BY category_name
        ");

        if ($duplicateCategories !== []) {
            $values = collect($duplicateCategories)
                ->map(fn ($row) => var_export($row->category_name, true) . " ({$row->count})")
                ->implode(', ');

            throw new RuntimeException("Cannot backfill expenses.category_id; duplicate expense_categories names: {$values}");
        }

        $unmatchedCategories = DB::select("
            SELECT e.`category`, COUNT(*) AS count
            FROM `expenses` e
            LEFT JOIN `expense_categories` ec
                ON LOWER(TRIM(e.`category`)) = LOWER(TRIM(ec.`name`))
            WHERE ec.`id` IS NULL
            GROUP BY e.`category`
            ORDER BY e.`category`
        ");

        if ($unmatchedCategories !== []) {
            $values = collect($unmatchedCategories)
                ->map(fn ($row) => var_export($row->category, true) . " ({$row->count})")
                ->implode(', ');

            throw new RuntimeException("Cannot backfill expenses.category_id; unmatched expense categories: {$values}");
        }
    }
};
