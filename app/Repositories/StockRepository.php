<?php

namespace App\Repositories;

use App\Models\StockItem;
use App\Models\StockLedger;
use App\Models\StockTransaction;
use App\Repositories\Contracts\StockRepositoryInterface;
use Illuminate\Support\Collection;

class StockRepository implements StockRepositoryInterface
{
    /**
     * Persist a new StockTransaction record.
     */
    public function createTransaction(array $data): StockTransaction
    {
        return StockTransaction::create($data);
    }

    /**
     * Persist a new StockLedger entry.
     */
    public function createLedgerEntry(array $data): void
    {
        StockLedger::create($data);
    }

    /**
     * Upsert the running stock summary for a given item.
     */
    public function updateSummary(string $itemName, float $quantityChange, string $unit, string $category = 'Feed'): void
    {
        $summary = StockItem::firstOrCreate(
            ['item_name' => $itemName],
            ['current_stock' => 0, 'unit' => $unit, 'category' => $category]
        );

        $summary->current_stock += $quantityChange;
        $summary->save();
    }

    /**
     * Find all transactions belonging to a polymorphic reference.
     */
    public function findTransactionsByReference(string $type, int $id): Collection
    {
        return StockTransaction::where('reference_type', $type)
            ->where('reference_id', $id)
            ->get();
    }

    /**
     * Delete StockLedger entries matching a given source.
     */
    public function deleteLedgerBySource(string $sourceType, int $sourceId): void
    {
        StockLedger::where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->delete();
    }

    /**
     * Return all items where current_stock < reorder_level.
     */
    public function getLowStockItems(): Collection
    {
        return StockItem::whereColumn('current_stock', '<', 'reorder_level')->get();
    }

    /**
     * Return all transactions within a date range.
     */
    public function getMovements(string $from, string $to): Collection
    {
        return StockTransaction::whereBetween('date', [$from, $to])
            ->orderBy('date', 'desc')
            ->get();
    }

    /**
     * Return current stock quantity by item name.
     */
    public function getCurrentStockByName(string $itemName): float
    {
        return (float) StockItem::where('item_name', $itemName)->value('current_stock') ?? 0.0;
    }
}
