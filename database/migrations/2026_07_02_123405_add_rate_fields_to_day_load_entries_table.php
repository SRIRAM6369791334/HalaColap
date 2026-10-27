<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('day_load_entries', function (Blueprint $table) {
            $table->decimal('paper_rate', 10, 2)->default(0.00)->after('dealer_id');
            $table->decimal('billing_rate', 10, 2)->default(0.00)->after('paper_rate');
            $table->decimal('customer_rate', 10, 2)->default(0.00)->after('billing_rate');
        });
    }

    public function down(): void
    {
        Schema::table('day_load_entries', function (Blueprint $table) {
            $table->dropColumn(['paper_rate', 'billing_rate', 'customer_rate']);
        });
    }
};
