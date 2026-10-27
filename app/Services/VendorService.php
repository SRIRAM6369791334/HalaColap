<?php

namespace App\Services;

use App\Models\Vendor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class VendorService
{
    public function search(?string $query, int $perPage = 15): LengthAwarePaginator
    {
        return Vendor::search($query)->orderBy('firm_name')->paginate($perPage);
    }

    public function create(array $data): Vendor
    {
        return Vendor::create($data);
    }

    public function update(Vendor $vendor, array $data): Vendor
    {
        $vendor->update($data);
        return $vendor->fresh();
    }

    public function delete(Vendor $vendor): void
    {
        $vendor->delete();
    }

    public function all(): \Illuminate\Database\Eloquent\Collection
    {
        return Vendor::orderBy('firm_name')->get();
    }
}
