<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `daily_bills` MODIFY `status` ENUM('COD', 'Pending', 'Bank', 'Paid') NOT NULL DEFAULT 'Pending'");
            DB::statement("ALTER TABLE `weekly_bills` MODIFY `status` ENUM('COD', 'Pending', 'Bank', 'Paid') NOT NULL DEFAULT 'Pending'");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            // Revert to original enum (Note: this might fail if there are any rows with status 'Paid')
            DB::statement("ALTER TABLE `daily_bills` MODIFY `status` ENUM('COD', 'Pending', 'Bank') NOT NULL DEFAULT 'Pending'");
            DB::statement("ALTER TABLE `weekly_bills` MODIFY `status` ENUM('COD', 'Pending', 'Bank') NOT NULL DEFAULT 'Pending'");
        }
    }
};
