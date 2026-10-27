<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('day_load_batches', function (Blueprint $table) {
            if (!Schema::hasColumn('day_load_batches', 'weight_loss_amount')) {
                $table->decimal('weight_loss_amount', 12, 2)->default(0.00)->after('total_loss_weight');
            }
            if (!Schema::hasColumn('day_load_batches', 'is_weight_loss_approved')) {
                $table->boolean('is_weight_loss_approved')->default(false)->after('total_loss_weight');
            }
            if (!Schema::hasColumn('day_load_batches', 'weight_loss_approved_by')) {
                $table->foreignId('weight_loss_approved_by')->nullable()->after('is_weight_loss_approved')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('day_load_batches', 'weight_loss_approved_at')) {
                $table->timestamp('weight_loss_approved_at')->nullable()->after('weight_loss_approved_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('day_load_batches', function (Blueprint $table) {
            if (Schema::hasColumn('day_load_batches', 'weight_loss_approved_by')) {
                $table->dropForeign(['weight_loss_approved_by']);
            }
            $columnsToDrop = array_filter([
                Schema::hasColumn('day_load_batches', 'weight_loss_amount') ? 'weight_loss_amount' : null,
                Schema::hasColumn('day_load_batches', 'is_weight_loss_approved') ? 'is_weight_loss_approved' : null,
                Schema::hasColumn('day_load_batches', 'weight_loss_approved_by') ? 'weight_loss_approved_by' : null,
                Schema::hasColumn('day_load_batches', 'weight_loss_approved_at') ? 'weight_loss_approved_at' : null,
            ]);
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
