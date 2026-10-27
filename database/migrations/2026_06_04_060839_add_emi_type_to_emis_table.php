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
        Schema::table('emis', function (Blueprint $table) {
            $table->string('emi_type')->default('Bank Loan')->after('id'); // Bank Loan, Customer, Dealer
            $table->unsignedBigInteger('entity_id')->nullable()->after('emi_type'); // customer_id or dealer_id if applicable
            $table->string('loan_name')->nullable()->change(); // Allow null for customer/dealer EMIs
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emis', function (Blueprint $table) {
            $table->dropColumn(['emi_type', 'entity_id']);
        });
    }
};
