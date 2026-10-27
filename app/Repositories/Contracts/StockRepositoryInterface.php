<?php

namespace App\Repositories\Contracts;

use App\Models\StockTransaction;
use Illuminate\Support\Collection;

interface StockRepositoryInterface
{
    public function createTransaction(array $data): StockTransaction;
    public function createLedgerEntry(array $data): void;
    public function updateSummary(string $itemName, float $quantityChange, string $unit, string $category): void;
    public function findTransactionsByReference(string $type, int $id): Collection;
    public function deleteLedgerBySource(string $sourceType, int $sourceId): void;
    public function getLowStockItems(): Collection;
    public function getMovements(string $from, string $to): Collection;
    public function getCurrentStockByName(string $itemName): float;
}
