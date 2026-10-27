<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dealer_payments', function (Blueprint $table) {
            $table->foreignId('day_load_entry_id')
                ->nullable()
                ->after('invoice_id')
                ->constrained('day_load_entries')
                ->nullOnDelete();

            $table->string('reference_number')->nullable()->after('notes');
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE dealer_payments MODIFY COLUMN payment_mode VARCHAR(50) NOT NULL DEFAULT 'Cash'");
        }

        Schema::table('vendor_payments', function (Blueprint $table) {
            $table->foreignId('day_load_entry_id')
                ->nullable()
                ->after('vendor_id')
                ->constrained('day_load_entries')
                ->nullOnDelete();

            $table->string('reference_number')->nullable()->after('notes');
            $table->decimal('pending_balance_after', 12, 2)->default(0)->after('amount');
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE vendor_payments MODIFY COLUMN payment_mode VARCHAR(50) NOT NULL DEFAULT 'Cash'");
        }
    }

    public function down(): void
    {
        Schema::table('dealer_payments', function (Blueprint $table) {
            $table->dropForeign(['day_load_entry_id']);
            $table->dropColumn(['day_load_entry_id', 'reference_number']);
        });

        Schema::table('vendor_payments', function (Blueprint $table) {
            $table->dropForeign(['day_load_entry_id']);
            $table->dropColumn(['day_load_entry_id', 'reference_number', 'pending_balance_after']);
        });
    }
};
