<?php

namespace App\Repositories\Contracts;

use App\Models\Purchase;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PurchaseRepositoryInterface
{
    public function paginated(?string $query, int $perPage): LengthAwarePaginator;
    public function findWithItems(int $id): Purchase;
    public function create(array $data): Purchase;
    public function update(Purchase $purchase, array $data): Purchase;
    public function delete(Purchase $purchase): bool;
    public function allForExport(): Collection;
    public function createItem(Purchase $purchase, array $itemData): \App\Models\PurchaseItem;
    public function deleteItems(Purchase $purchase): void;
}
