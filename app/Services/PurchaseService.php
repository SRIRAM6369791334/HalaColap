<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Item;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function paginated(?string $query, int $perPage = 15): LengthAwarePaginator
    {
        return Purchase::with(['vendor', 'items.item'])
            ->search($query)
            ->latest('date')
            ->paginate($perPage);
    }

    public function create(array $data): Purchase
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'] ?? [];
            unset($data['items']);
            
            // Calculate total_amount and gst_amount
            $subtotal = 0;
            foreach ($items as $itemData) {
                $subtotal += ($itemData['qty'] ?? 0) * ($itemData['rate'] ?? 0);
            }
            $gstPercent = $data['gst_percentage'] ?? 0;
            $data['gst_amount'] = $subtotal * ($gstPercent / 100);
            $data['total_amount'] = $subtotal + $data['gst_amount'];
            
            // Find and set vendor_id
            $vendorModel = \App\Models\Vendor::where('firm_name', $data['vendor_name'] ?? '')->first();
            if ($vendorModel) {
                $data['vendor_id'] = $vendorModel->id;
            }

            if (empty($data['invoice_no'])) {
                $data['invoice_no'] = app(\App\Services\InvoiceNumberService::class)->generateUnique('INV', 'purchases');
            }

            // Grand totals are handled by client-side or calculated here
            $purchase = Purchase::create($data);
            
            foreach ($items as $itemData) {
                // Fetch item details if item_id is provided
                $itemId = $itemData['item_id'] ?? null;
                $itemMaster = null;

                if ($itemId) {
                    $itemMaster = Item::find($itemId);
                }

                if (!$itemMaster) {
                    $itemName = $itemData['name'] ?? 'Unknown';
                    $itemMaster = Item::firstOrCreate(
                        ['name' => $itemName],
                        [
                            'code' => strtoupper(substr($itemName, 0, 3)) . '-' . rand(1000, 9999),
                            'type' => 'Other',
                            'category' => 'Uncategorized',
                            'base_unit' => $itemData['unit'] ?? 'kg',
                            'is_active' => true
                        ]
                    );
                }

                $itemData['item_id'] = $itemMaster->id;
                $itemName = $itemMaster->name;

                $purchaseItem = $purchase->items()->create([
                    'item_name'    => $itemName,
                    'quantity'     => $itemData['qty'],
                    'unit'         => $itemData['unit'] ?? 'kg',
                    'rate'         => $itemData['rate'],
                    'tax_amount'   => $itemData['tax_amount'] ?? 0,
                    'total_amount' => $itemData['total_amount'] ?? ($itemData['qty'] * $itemData['rate']),
                ]);

                // Record stock movement if item_id is linked
                $itemId = $itemData['item_id'] ?? null;
                if ($itemId) {
                    app(StockService::class)->recordIn([
                        'item_id'          => $itemId,
                        'batch_id'         => $itemData['batch_id'] ?? null,
                        'warehouse_id'     => $itemData['warehouse_id'] ?? null,
                        'quantity'         => $purchaseItem->quantity,
                        'unit'             => $purchaseItem->unit,
                        'source_type'      => 'Purchase',
                        'source_id'        => $purchaseItem->id,
                        'transaction_date' => $purchase->date,
                        'remarks'          => "Purchase from {$purchase->vendor_name} (Inv: {$purchase->invoice_no})",
                    ]);
                }
            }
            
            $paymentMethod = null;
            if (($data['payment_mode'] ?? '') === 'Cash') {
                $paymentMethod = 'Cash';
            } elseif (in_array($data['payment_mode'] ?? '', ['UPI', 'NEFT', 'Cheque(Bank Transfer)'])) {
                $paymentMethod = 'Bank Transfer';
            }

            // Record direct expense for this purchase
            \App\Models\Expense::create([
                'date'           => $purchase->date,
                'category'       => 'Purchase',
                'description'    => "Purchase from {$purchase->vendor_name} (Inv: " . ($purchase->invoice_no ?: 'N/A') . ") [Ref: PR-{$purchase->id}]",
                'amount'         => $purchase->total_amount,
                'payment_method' => $paymentMethod,
            ]);

            // Recalculate cash/bank ledger
            app(CashBankLedgerService::class)->recalculateForDate(\Carbon\Carbon::parse($purchase->date));
            
            // Create EMI schedules if payment mode is Pay later(EMI)
            if (($data['payment_mode'] ?? '') === 'Pay later(EMI)' && isset($data['emis']) && is_array($data['emis'])) {
                foreach ($data['emis'] as $emiData) {
                    \App\Models\Emi::create([
                        'emi_type'   => 'Vendor',
                        'entity_id'  => $purchase->vendor_id,
                        'loan_name'  => 'Purchase EMI - ' . ($purchase->invoice_no ?: 'PR-'.$purchase->id),
                        'bank_name'  => 'Purchase Bill',
                        'amount'     => $emiData['amount'],
                        'due_date'   => $emiData['due_date'],
                        'status'     => 'Upcoming',
                    ]);
                }
            }
            
            return $purchase;
        });
    }

    public function find($id): Purchase
    {
        return Purchase::with('items.item')->findOrFail($id);
    }

    public function update(Purchase $purchase, array $data): bool
    {
        return DB::transaction(function () use ($purchase, $data) {
            $items = $data['items'] ?? [];
            unset($data['items']);
            
            // Calculate total_amount and gst_amount
            $subtotal = 0;
            foreach ($items as $itemData) {
                $subtotal += ($itemData['qty'] ?? 0) * ($itemData['rate'] ?? 0);
            }
            $gstPercent = $data['gst_percentage'] ?? 0;
            $data['gst_amount'] = $subtotal * ($gstPercent / 100);
            $data['total_amount'] = $subtotal + $data['gst_amount'];

            // Important: On update, we need to handle existing stock ledger and transaction entries
            $stockService = app(StockService::class);
            foreach ($purchase->items as $item) {
                $stockService->revertMovement(\App\Models\PurchaseItem::class, $item->id);
            }

            $oldDate = $purchase->date;
            
            // Find and set vendor_id
            $vendorModel = \App\Models\Vendor::where('firm_name', $data['vendor_name'] ?? '')->first();
            if ($vendorModel) {
                $data['vendor_id'] = $vendorModel->id;
            }

            $purchase->items()->delete();
            $purchase->update($data);

            foreach ($items as $itemData) {
                $itemId = $itemData['item_id'] ?? null;
                $itemMaster = null;

                if ($itemId) {
                    $itemMaster = Item::find($itemId);
                }

                if (!$itemMaster) {
                    $itemName = $itemData['name'] ?? 'Unknown';
                    $itemMaster = Item::firstOrCreate(
                        ['name' => $itemName],
                        [
                            'code' => strtoupper(substr($itemName, 0, 3)) . '-' . rand(1000, 9999),
                            'type' => 'Other',
                            'category' => 'Uncategorized',
                            'base_unit' => $itemData['unit'] ?? 'kg',
                            'is_active' => true
                        ]
                    );
                }

                $itemData['item_id'] = $itemMaster->id;
                $itemName = $itemMaster->name;

                $purchaseItem = $purchase->items()->create([
                    'item_name'    => $itemName,
                    'quantity'     => $itemData['qty'],
                    'unit'         => $itemData['unit'] ?? 'kg',
                    'rate'         => $itemData['rate'],
                    'tax_amount'   => $itemData['tax_amount'] ?? 0,
                    'total_amount' => $itemData['total_amount'] ?? ($itemData['qty'] * $itemData['rate']),
                ]);

                $itemId = $itemData['item_id'] ?? null;
                if ($itemId) {
                    $stockService->recordIn([
                        'item_id'          => $itemId,
                        'batch_id'         => $itemData['batch_id'] ?? null,
                        'warehouse_id'     => $itemData['warehouse_id'] ?? null,
                        'quantity'         => $purchaseItem->quantity,
                        'unit'             => $purchaseItem->unit,
                        'source_type'      => 'Purchase',
                        'source_id'        => $purchaseItem->id,
                        'transaction_date' => $purchase->date,
                        'remarks'          => "Updated Purchase from {$purchase->vendor_name}",
                    ]);
                }
            }
            
            // Revert and record updated expense entry
            \App\Models\Expense::where('description', 'like', "%[Ref: PR-{$purchase->id}]%")->delete();

            $paymentMethod = null;
            if (($purchase->payment_mode ?? '') === 'Cash') {
                $paymentMethod = 'Cash';
            } elseif (in_array($purchase->payment_mode ?? '', ['UPI', 'NEFT', 'Cheque(Bank Transfer)'])) {
                $paymentMethod = 'Bank Transfer';
            }

            \App\Models\Expense::create([
                'date'           => $purchase->date,
                'category'       => 'Purchase',
                'description'    => "Purchase from {$purchase->vendor_name} (Inv: " . ($purchase->invoice_no ?: 'N/A') . ") [Ref: PR-{$purchase->id}]",
                'amount'         => $purchase->total_amount,
                'payment_method' => $paymentMethod,
            ]);

            // Recalculate cash/bank ledger for both old and new dates
            app(CashBankLedgerService::class)->recalculateForDate(\Carbon\Carbon::parse($oldDate));
            app(CashBankLedgerService::class)->recalculateForDate(\Carbon\Carbon::parse($purchase->date));
            
            return true;
        });
    }

    public function delete(Purchase $purchase): bool
    {
        return DB::transaction(function () use ($purchase) {
            $purchaseDate = $purchase->date;
            $stockService = app(StockService::class);
            foreach ($purchase->items as $item) {
                $stockService->revertMovement(\App\Models\PurchaseItem::class, $item->id);
            }
            
            if ($purchase->payment_mode === 'Credit') {
                // Previously, this decremented dealer's pending_amount.
                // Now, Vendor calculates its outstanding_balance dynamically.
            }

            // Delete corresponding expense entry
            \App\Models\Expense::where('description', 'like', "%[Ref: PR-{$purchase->id}]%")->delete();
            
            $deleted = $purchase->delete();

            // Recalculate cash/bank ledger
            app(CashBankLedgerService::class)->recalculateForDate(\Carbon\Carbon::parse($purchaseDate));

            return $deleted;
        });
    }

    public function allForExport(): Collection
    {
        return Purchase::with('items')->orderByDesc('date')->get();
    }
}
