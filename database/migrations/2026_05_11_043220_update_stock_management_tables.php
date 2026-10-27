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
        // Check if table exists before renaming to avoid errors if already renamed
        if (Schema::hasTable('stock_summary') && !Schema::hasTable('stock_items')) {
            Schema::rename('stock_summary', 'stock_items');
        }

        Schema::table('stock_items', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_items', 'category')) {
                $table->string('category')->after('item_name')->default('Feed');
            }
            if (!Schema::hasColumn('stock_items', 'reorder_level')) {
                $table->decimal('reorder_level', 10, 3)->default(0)->after('current_stock');
            }
        });

        if (Schema::hasTable('stock_movements') && !Schema::hasTable('stock_transactions')) {
            Schema::rename('stock_movements', 'stock_transactions');
        }

        Schema::table('stock_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_transactions', 'txn_type')) {
                $table->enum('txn_type', ['IN', 'OUT', 'ADJUST'])->after('id')->nullable();
            }
        });

        Schema::create('bird_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_name');
            $table->date('date_received');
            $table->integer('initial_count');
            $table->integer('current_count');
            $table->decimal('avg_weight', 8, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bird_batches');
        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->dropColumn('txn_type');
        });
        Schema::rename('stock_transactions', 'stock_movements');
        Schema::table('stock_items', function (Blueprint $table) {
            $table->dropColumn(['category', 'reorder_level']);
        });
        Schema::rename('stock_items', 'stock_summary');
    }
};
