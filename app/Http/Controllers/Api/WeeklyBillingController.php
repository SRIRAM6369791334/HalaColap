<?php

namespace App\Http\Controllers\Api;

use App\Services\Tax\GSTCalculator;
use App\Models\Customer;
use App\Models\WeeklyBill;
use App\Services\InvoiceNumberService;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WeeklyBillingController extends BaseApiController
{
    /**
     * Get a paginated list of weekly bills.
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->input('search');
        $perPage = min((int)$request->input('per_page', 15), 100);

        $bills = WeeklyBill::with(['dealer', 'items'])
            ->search($search)
            ->latest()
            ->paginate($perPage);

        return $this->sendResponse([
            'bills'      => $bills->items(),
            'pagination' => [
                'current_page' => $bills->currentPage(),
                'last_page'    => $bills->lastPage(),
                'per_page'     => $bills->perPage(),
                'total'        => $bills->total(),
            ]
        ], 'Weekly bills retrieved successfully');
    }

    /**
     * Store a new weekly bill.
     */
    public function store(Request $request, InvoiceNumberService $invoiceService): JsonResponse
    {
        $tenantId = app(\App\Services\Tenancy\TenantContext::class)->getTenantId() ?? auth()->user()?->current_tenant_id;

        $validator = Validator::make($request->all(), [
            'customer_id'  => [
                'required',
                \Illuminate\Validation\Rule::exists('customers', 'id')->where(fn($q) => $tenantId ? $q->where('tenant_id', $tenantId) : $q),
            ],
            'period_start' => 'required|date',
            'period_end'   => 'required|date|after_or_equal:period_start',
            'status'       => 'required|in:Generated,Pending,Paid',
            'items'        => 'required|array|min:1',
            'items.*.name' => 'required|string|max:255',
            'items.*.qty'  => 'required|numeric|min:0.01',
            'items.*.rate' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation error', $validator->errors()->toArray(), 422);
        }

        try {
            $bill = DB::transaction(function () use ($request, $invoiceService) {
                $itemsData = $request->input('items');
                
                $subtotal = 0;
                foreach ($itemsData as $item) {
                    $subtotal += $item['qty'] * $item['rate'];
                }

                $gstData = GSTCalculator::calculate($subtotal, 18);
                
                $bill = WeeklyBill::create([
                    'customer_id'    => $request->input('customer_id'),
                    'period_start'   => $request->input('period_start'),
                    'period_end'     => $request->input('period_end'),
                    'invoice_no'     => $invoiceService->generateUnique('INV-W', 'weekly_bills'),
                    'amount'         => $subtotal,
                    'gst_percentage' => 18,
                    'gst_amount'     => $gstData['total_gst'],
                    'net_amount'     => $gstData['net_amount'],
                    'status'         => $request->input('status'),
                    'payment_mode'   => 'Cash', // Default
                ]);

                foreach ($itemsData as $item) {
                    $base = $item['qty'] * $item['rate'];
                    $tax = round($base * 18 / 100, 2);

                    $billItem = $bill->items()->create([
                        'item_name'    => $item['name'],
                        'quantity_kg'  => $item['qty'],
                        'rate_per_kg'  => $item['rate'],
                        'tax_amount'   => $tax,
                        'total_amount' => $base + $tax,
                    ]);

                    // Auto-trigger stock movement for each item
                    app(StockService::class)->recordOut([
                        'item_name'      => $billItem->item_name,
                        'quantity'       => $billItem->quantity_kg,
                        'rate'           => $billItem->rate_per_kg,
                        'reference_type' => WeeklyBill::class,
                        'reference_id'   => $bill->id,
                        'date'           => $bill->period_end,
                        'created_by'     => auth()->id() ?? 1,
                    ]);
                }

                return $bill->load(['customer', 'items']);
            });

            return $this->sendResponse($bill, 'Weekly bill created successfully', 201);

        } catch (\Exception $e) {
            return $this->sendError('Could not create weekly bill', ['exception' => $e->getMessage()], 500);
        }
    }

    /**
     * Store multiple weekly bills in bulk.
     */
    public function bulkStore(Request $request, InvoiceNumberService $invoiceService): JsonResponse
    {
        $tenantId = app(\App\Services\Tenancy\TenantContext::class)->getTenantId() ?? auth()->user()?->current_tenant_id;

        $validator = Validator::make($request->all(), [
            'customer_ids'   => 'required|array',
            'customer_ids.*' => [
                \Illuminate\Validation\Rule::exists('customers', 'id')->where(fn($q) => $tenantId ? $q->where('tenant_id', $tenantId) : $q),
            ],
            'period_start'   => 'required|date',
            'period_end'     => 'required|date|after_or_equal:period_start',
            'amount'         => 'required|numeric|min:0.01',
            'status'         => 'required|in:Generated,Pending',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation error', $validator->errors()->toArray(), 422);
        }

        try {
            $billsCount = DB::transaction(function () use ($request, $invoiceService) {
                $count = 0;
                foreach ($request->customer_ids as $cid) {
                    $gstData = GSTCalculator::calculate($request->amount, 18);

                    $bill = WeeklyBill::create([
                        'invoice_no'     => $invoiceService->generateUnique('INV-W', 'weekly_bills'),
                        'customer_id'    => $cid,
                        'period_start'   => $request->period_start,
                        'period_end'     => $request->period_end,
                        'amount'         => $request->amount,
                        'gst_percentage' => 18,
                        'gst_amount'     => $gstData['total_gst'],
                        'net_amount'     => $gstData['net_amount'],
                        'status'         => $request->status,
                        'payment_mode'   => 'Cash',
                    ]);

                    $bill->items()->create([
                        'item_name'    => 'Weekly MSR BROILERS Settlement',
                        'quantity_kg'  => 1,
                        'rate_per_kg'  => $request->amount,
                        'tax_amount'   => $gstData['total_gst'],
                        'total_amount' => $gstData['net_amount'],
                    ]);

                    $count++;
                }
                return $count;
            });

            return $this->sendResponse([
                'generated_bills' => $billsCount
            ], "{$billsCount} weekly bills successfully generated in bulk", 201);

        } catch (\Exception $e) {
            return $this->sendError('Could not process bulk weekly bills', ['exception' => $e->getMessage()], 500);
        }
    }

    /**
     * Display weekly bill details.
     */
    public function show(WeeklyBill $weeklyBill): JsonResponse
    {
        $weeklyBill->load(['customer', 'items']);
        return $this->sendResponse($weeklyBill, 'Weekly bill retrieved successfully');
    }

    /**
     * Delete a weekly bill.
     */
    public function destroy(WeeklyBill $weeklyBill): JsonResponse
    {
        try {
            DB::transaction(function () use ($weeklyBill) {
                // Delete stock transactions related to this weekly bill
                DB::table('stock_transactions')
                    ->where('reference_type', WeeklyBill::class)
                    ->where('reference_id', $weeklyBill->id)
                    ->delete();

                // Delete the items
                $weeklyBill->items()->delete();

                // Delete the bill itself
                $weeklyBill->delete();
            });

            return $this->sendResponse([], 'Weekly bill and associated stock records deleted successfully');

        } catch (\Exception $e) {
            return $this->sendError('Could not delete weekly bill', ['exception' => $e->getMessage()], 500);
        }
    }

    /**
     * Get WhatsApp shareable metadata & deep link.
     */
    public function shareUrl(WeeklyBill $weeklyBill): JsonResponse
    {
        $weeklyBill->load('customer');
        $phone = preg_replace('/[^0-9]/', '', $weeklyBill->customer->phone ?? '');
        if (!$phone) {
            return $this->sendError('Phone missing', ['phone' => 'Customer phone number is not set'], 400);
        }

        $text = "Hello {$weeklyBill->customer->name}, your MSR BROILERS bill for period " . 
                ($weeklyBill->period_start ? $weeklyBill->period_start->format('d M') : '') . " to " . 
                ($weeklyBill->period_end ? $weeklyBill->period_end->format('d M') : '') . " is ₹" . 
                number_format($weeklyBill->amount, 2) . ". Thank you!";
        
        $encodedText = urlencode($text);
        $shareUrl = "https://wa.me/91{$phone}?text={$encodedText}";

        return $this->sendResponse([
            'phone'     => '91' . $phone,
            'message'   => $text,
            'share_url' => $shareUrl
        ], 'WhatsApp sharing URL generated successfully');
    }
}
