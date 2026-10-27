<?php

namespace App\Services;

use App\Models\Dealer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DealerService
{
    public function search(?string $query, int $perPage = 15): LengthAwarePaginator
    {
        return Dealer::search($query)
            ->orderByRaw('CASE WHEN sort_order IS NOT NULL THEN 0 ELSE 1 END')
            ->orderBy('sort_order', 'asc')
            ->orderBy('firm_name', 'asc')
            ->paginate($perPage);
    }

    public function create(array $data): Dealer
    {
        return Dealer::create($data);
    }

    public function update(Dealer $dealer, array $data): Dealer
    {
        $dealer->update($data);
        return $dealer->fresh();
    }

    public function delete(Dealer $dealer): void
    {
        $dealer->delete();
    }

    public function all(): \Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection
    {
        return self::arrangeBySlotSequence(Dealer::all());
    }

    /**
     * Positionally arranges a collection of dealers into slots.
     * Dealers with explicit sort_order occupy their designated 1-based slot (e.g. 5 occupies slot 5).
     * Unassigned dealers (sort_order = null or 0) fill the remaining available slots in A-Z order.
     */
    public static function arrangeBySlotSequence($dealers): \Illuminate\Support\Collection
    {
        $explicit = [];
        $unassigned = [];

        foreach ($dealers as $d) {
            $order = (int) $d->sort_order;
            if ($order > 0) {
                $explicit[$order][] = $d;
            } else {
                $unassigned[] = $d;
            }
        }

        // Sort unassigned alphabetically by firm_name
        usort($unassigned, fn($a, $b) => strcasecmp($a->firm_name, $b->firm_name));

        // Sort explicit lists by firm_name if multiple have same order
        foreach ($explicit as &$list) {
            usort($list, fn($a, $b) => strcasecmp($a->firm_name, $b->firm_name));
        }
        unset($list);
        ksort($explicit);

        $result = [];
        $slot = 1;
        $totalCount = count($dealers);
        $unassignedIdx = 0;

        while (count($result) < $totalCount) {
            if (!empty($explicit[$slot])) {
                foreach ($explicit[$slot] as $item) {
                    $result[] = $item;
                }
            } elseif ($unassignedIdx < count($unassigned)) {
                $result[] = $unassigned[$unassignedIdx++];
            } else {
                // Unassigned are exhausted, append any remaining explicit items
                foreach ($explicit as $key => $items) {
                    if ($key > $slot && !empty($items)) {
                        foreach ($items as $item) {
                            $result[] = $item;
                        }
                        unset($explicit[$key]);
                        break;
                    }
                }
            }
            $slot++;
        }

        return collect($result);
    }
}
