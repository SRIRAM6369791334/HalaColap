<?php

namespace App\Repositories;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Repositories\Contracts\PurchaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PurchaseRepository implements PurchaseRepositoryInterface
{
    /**
     * Return a paginated, searchable list of purchases.
     */
    public function paginated(?string $query, int $perPage = 15): LengthAwarePaginator
    {
        return Purchase::with(['vendor', 'items.item'])
            ->search($query)
            ->latest('date')
            ->paginate($perPage);
    }

    /**
     * Find a single purchase with its items eagerly loaded.
     */
    public function findWithItems(int $id): Purchase
    {
        return Purchase::with('items.item')->findOrFail($id);
    }

    /**
     * Persist a new Purchase record.
     */
    public function create(array $data): Purchase
    {
        return Purchase::create($data);
    }

    /**
     * Update an existing Purchase record.
     */
    public function update(Purchase $purchase, array $data): Purchase
    {
        $purchase->update($data);
        return $purchase->fresh();
    }

    /**
     * Hard-delete a Purchase record.
     */
    public function delete(Purchase $purchase): bool
    {
        return (bool) $purchase->delete();
    }

    /**
     * Return all purchases for CSV/PDF export (no pagination).
     */
    public function allForExport(): Collection
    {
        return Purchase::with('items')->orderByDesc('date')->get();
    }

    /**
     * Create a PurchaseItem for the given purchase.
     */
    public function createItem(Purchase $purchase, array $itemData): PurchaseItem
    {
        return $purchase->items()->create($itemData);
    }

    /**
     * Delete all items belonging to a purchase (used before re-sync on update).
     */
    public function deleteItems(Purchase $purchase): void
    {
        $purchase->items()->delete();
    }
}
