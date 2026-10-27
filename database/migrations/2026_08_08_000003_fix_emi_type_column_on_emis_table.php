<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE `emis` MODIFY `emi_type` VARCHAR(255) NOT NULL DEFAULT 'Bank Loan'");
            DB::statement("ALTER TABLE `emis` MODIFY `loan_name` VARCHAR(255) NULL");
        } else {
            Schema::table('emis', function (Blueprint $table) {
                $table->string('emi_type')->default('Bank Loan')->change();
                $table->string('loan_name')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        // No revert needed
    }
};
