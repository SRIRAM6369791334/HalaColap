<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->assertEmiTypesCanBeMapped();
        $this->normalizeEmiTypes();

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `emis` MODIFY `emi_type` ENUM('Bank', 'Finance Company') NOT NULL DEFAULT 'Bank'");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `emis` MODIFY `emi_type` VARCHAR(255) NOT NULL DEFAULT 'Bank Loan'");
        }

        DB::table('emis')
            ->where('emi_type', 'Bank')
            ->update(['emi_type' => 'Bank Loan']);
    }

    private function normalizeEmiTypes(): void
    {
        foreach ($this->emiTypeMappings() as $target => $values) {
            $placeholders = implode(', ', array_fill(0, count($values), '?'));

            DB::table('emis')
                ->whereRaw("LOWER(TRIM(`emi_type`)) IN ({$placeholders})", $values)
                ->update(['emi_type' => $target]);
        }
    }

    private function assertEmiTypesCanBeMapped(): void
    {
        $allowedValues = array_merge(...array_values($this->emiTypeMappings()));
        $placeholders = implode(', ', array_fill(0, count($allowedValues), '?'));

        $invalidTypes = DB::table('emis')
            ->select('emi_type', DB::raw('COUNT(*) as count'))
            ->where(function ($query) use ($allowedValues, $placeholders) {
                $query
                    ->whereNull('emi_type')
                    ->orWhereRaw("LOWER(TRIM(`emi_type`)) NOT IN ({$placeholders})", $allowedValues);
            })
            ->groupBy('emi_type')
            ->orderBy('emi_type')
            ->get();

        if ($invalidTypes->isNotEmpty()) {
            $values = $invalidTypes
                ->map(fn ($row) => var_export($row->emi_type, true) . " ({$row->count})")
                ->implode(', ');

            throw new RuntimeException(
                "Cannot convert emis.emi_type to enum; review/reassign unsupported EMI types first: {$values}"
            );
        }
    }

    private function emiTypeMappings(): array
    {
        return [
            'Bank' => ['bank', 'bank loan', 'bankloan'],
            'Finance Company' => ['finance company', 'finance', 'finance loan', 'finance company loan'],
        ];
    }
};
