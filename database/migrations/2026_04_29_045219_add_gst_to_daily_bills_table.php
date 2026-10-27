<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_bills', function (Blueprint $table) {
            $table->decimal('gst_percentage', 5, 2)->default(0)->after('amount');
            $table->decimal('gst_amount', 10, 2)->default(0)->after('gst_percentage');
            $table->decimal('net_amount', 10, 2)->default(0)->after('gst_amount');
            $table->string('payment_mode')->default('cash')->after('net_amount');
        });
    }

    public function down(): void
    {
        Schema::table('daily_bills', function (Blueprint $table) {
            $table->dropColumn(['gst_percentage', 'gst_amount', 'net_amount', 'payment_mode']);
        });
    }
};
