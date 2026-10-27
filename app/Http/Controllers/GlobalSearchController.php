<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Dealer;
use App\Models\Vendor;
use App\Models\DailyBill;
use App\Models\WeeklyBill;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GlobalSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $term = trim($request->get('q', ''));

        if (strlen($term) < 2) {
            return response()->json([]);
        }

        // ── Customers ──────────────────────────────────────────────────────
        $customers = Customer::search($term)->limit(4)->get()->toBase()->map(fn($c) => [
            'id'    => $c->id,
            'type'  => 'Customer',
            'label' => $c->name,
            'sub'   => $c->phone ?? $c->route ?? '—',
            'icon'  => 'person',
            'color' => 'emerald',
            'url'   => route('masters.customers.show', $c->id),
        ]);

        // ── Dealers ────────────────────────────────────────────────────────
        $dealers = Dealer::search($term)->limit(4)->get()->toBase()->map(fn($d) => [
            'id'    => $d->id,
            'type'  => 'Dealer',
            'label' => $d->firm_name,
            'sub'   => $d->contact_person ?? $d->phone ?? '—',
            'icon'  => 'storefront',
            'color' => 'blue',
            'url'   => route('masters.dealers.show', $d->id),
        ]);

        // ── Vendors ────────────────────────────────────────────────────────
        $vendors = Vendor::search($term)->limit(4)->get()->toBase()->map(fn($v) => [
            'id'    => $v->id,
            'type'  => 'Vendor',
            'label' => $v->firm_name,
            'sub'   => $v->contact_person ?? $v->phone ?? '—',
            'icon'  => 'local_shipping',
            'color' => 'amber',
            'url'   => route('masters.vendors.show', $v->id),
        ]);

        // ── Daily Bills (Invoice Search) ───────────────────────────────────
        // invoice_number is a computed accessor: "INV-D-0016"
        // Extract numeric ID from search term if it matches the pattern
        $dailyBills = collect();
        $dailyQuery = DailyBill::with('customer')->limit(4);

        // Direct invoice_no column search (stored in DB for newer records)
        $byInvoiceNo = (clone $dailyQuery)->where('invoice_no', 'like', "%{$term}%")->get();

        // Also extract numeric ID if user types "INV-D-0016" or "0016" or "16"
        $extractedId = $this->extractId($term, 'INV-D-');
        $byId = $extractedId
            ? DailyBill::with('customer')->where('id', $extractedId)->limit(4)->get()
            : collect();

        $dailyBills = $byInvoiceNo->merge($byId)->unique('id')->toBase()->map(fn($b) => [
            'id'    => $b->id,
            'type'  => 'Daily Invoice',
            'label' => $b->invoice_number,
            'sub'   => ($b->customer->name ?? '—') . ' · ₹' . number_format($b->net_amount, 0),
            'icon'  => 'receipt',
            'color' => 'violet',
            'url'   => route('billing.daily.invoice', $b->id),
        ]);

        // ── Weekly Bills (Invoice Search) ──────────────────────────────────
        // invoice_no is a real DB column in weekly_bills
        $weeklyQuery = WeeklyBill::with('dealer')->limit(4);
        $byWeeklyInvoice = (clone $weeklyQuery)->where('invoice_no', 'like', "%{$term}%")->get();

        $extractedWeeklyId = $this->extractId($term, 'INV-W-');
        $byWeeklyId = $extractedWeeklyId
            ? WeeklyBill::with('dealer')->where('id', $extractedWeeklyId)->limit(4)->get()
            : collect();

        $weeklyBills = $byWeeklyInvoice->merge($byWeeklyId)->unique('id')->toBase()->map(fn($b) => [
            'id'    => $b->id,
            'type'  => 'Weekly Invoice',
            'label' => $b->invoice_number,
            'sub'   => ($b->dealer->firm_name ?? '—') . ' · ₹' . number_format($b->net_amount, 0),
            'icon'  => 'receipt_long',
            'color' => 'purple',
            'url'   => route('billing.weekly.show', $b->id),
        ]);

        // ── Purchase Invoices ──────────────────────────────────────────────
        // invoice_no is a real DB column: "INV-2026-0016"
        $purchases = Purchase::with('vendor')
            ->where(function($q) use ($term) {
                $q->where('invoice_no', 'like', "%{$term}%")
                  ->orWhere('vendor_name', 'like', "%{$term}%");
            })
            ->limit(4)->get()->toBase()->map(fn($p) => [
                'id'    => $p->id,
                'type'  => 'Purchase Invoice',
                'label' => $p->invoice_no ?? 'INV-#'.$p->id,
                'sub'   => ($p->vendor_name ?? '—') . ' · ₹' . number_format($p->total_amount, 0),
                'icon'  => 'shopping_cart',
                'color' => 'orange',
                'url'   => route('purchases.show', $p->id),
            ]);

        // ── Merge all results ──────────────────────────────────────────────
        $results = $customers
            ->merge($dealers)
            ->merge($vendors)
            ->merge($purchases)
            ->merge($dailyBills)
            ->merge($weeklyBills)
            ->values();

        return response()->json($results);
    }

    /**
     * Extract numeric ID from patterns like:
     *   "INV-D-0016" → 16
     *   "INV-2026-0016" → null (not matched)
     *   "0016" → 16
     *   "16" → 16
     */
    private function extractId(string $term, string $prefix): ?int
    {
        // If term starts with the prefix, strip it and parse
        if (stripos($term, $prefix) === 0) {
            $numeric = ltrim(substr($term, strlen($prefix)), '0');
            return is_numeric($numeric) && $numeric !== '' ? (int) $numeric : null;
        }

        // If pure numeric (e.g. "16" or "0016")
        $stripped = ltrim($term, '0');
        if (is_numeric($term) && $stripped !== '') {
            return (int) $stripped;
        }

        return null;
    }
}

