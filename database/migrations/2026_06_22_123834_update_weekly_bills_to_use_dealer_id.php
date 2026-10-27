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
        Schema::table('weekly_bills', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->renameColumn('customer_id', 'dealer_id');
            $table->foreign('dealer_id')->references('id')->on('dealers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weekly_bills', function (Blueprint $table) {
            $table->dropForeign(['dealer_id']);
            $table->renameColumn('dealer_id', 'customer_id');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });
    }
};
