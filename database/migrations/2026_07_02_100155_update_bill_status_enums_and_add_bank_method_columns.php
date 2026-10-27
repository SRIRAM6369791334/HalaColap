<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->assertBillStatusesCanBeMapped('daily_bills');
        $this->assertBillStatusesCanBeMapped('weekly_bills');

        $this->normalizeBillStatuses('daily_bills');
        $this->normalizeBillStatuses('weekly_bills');

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `daily_bills` MODIFY `status` ENUM('COD', 'Pending', 'Bank') NOT NULL DEFAULT 'Pending'");
            DB::statement("ALTER TABLE `weekly_bills` MODIFY `status` ENUM('COD', 'Pending', 'Bank') NOT NULL DEFAULT 'Pending'");
        }

        Schema::table('daily_bills', function (Blueprint $table) {
            $table->enum('bank_method', ['UPI', 'Cheque', 'NEFT'])->nullable()->after('payment_mode');
        });

        Schema::table('weekly_bills', function (Blueprint $table) {
            $table->enum('bank_method', ['UPI', 'Cheque', 'NEFT'])->nullable()->after('payment_mode');
        });
    }

    public function down(): void
    {
        Schema::table('daily_bills', function (Blueprint $table) {
            $table->dropColumn('bank_method');
        });

        Schema::table('weekly_bills', function (Blueprint $table) {
            $table->dropColumn('bank_method');
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `daily_bills` MODIFY `status` VARCHAR(255) NOT NULL DEFAULT 'Pending'");
            DB::statement("ALTER TABLE `weekly_bills` MODIFY `status` VARCHAR(255) NOT NULL DEFAULT 'Pending'");
        }
    }

    private function normalizeBillStatuses(string $table): void
    {
        foreach ($this->billStatusMappings() as $target => $values) {
            $placeholders = implode(', ', array_fill(0, count($values), '?'));

            DB::table($table)
                ->whereRaw("LOWER(TRIM(`status`)) IN ({$placeholders})", $values)
                ->update(['status' => $target]);
        }
    }

    private function assertBillStatusesCanBeMapped(string $table): void
    {
        $allowedValues = array_merge(...array_values($this->billStatusMappings()));
        $placeholders = implode(', ', array_fill(0, count($allowedValues), '?'));

        $unknownStatuses = DB::table($table)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->where(function ($query) use ($allowedValues, $placeholders) {
                $query
                    ->whereNull('status')
                    ->orWhereRaw("LOWER(TRIM(`status`)) NOT IN ({$placeholders})", $allowedValues);
            })
            ->groupBy('status')
            ->orderBy('status')
            ->get();

        if ($unknownStatuses->isNotEmpty()) {
            $values = $unknownStatuses
                ->map(fn ($row) => var_export($row->status, true) . " ({$row->count})")
                ->implode(', ');

            throw new RuntimeException("Cannot convert {$table}.status to enum; unmapped values: {$values}");
        }
    }

    private function billStatusMappings(): array
    {
        return [
            'COD' => ['cod', 'cash', 'cash on delivery', 'cash-on-delivery', 'cash_on_delivery'],
            'Bank' => ['bank', 'bank transfer', 'bank-transfer', 'bank_transfer', 'upi', 'cheque', 'neft', 'online', 'online transfer'],
            'Pending' => ['pending', 'unpaid', 'due', 'credit', 'part', 'partial', 'open', 'outstanding', ''],
        ];
    }
};
