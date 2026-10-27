<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Dealer;
use App\Models\DealerPayment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Store a new dealer payment and auto-deduct the pending amount.
     */
    public function storeDealerPayment(Request $request)
    {
        $validated = $request->validate([
            'dealer_id' => 'required|exists:dealers,id',
            'amount'    => 'required|numeric|min:0.01',
            'date'      => 'required|date',
            'payment_mode' => 'required|string',
            'notes'     => 'nullable|string',
        ]);

        // 1. Create the payment record
        $payment = DealerPayment::create([
            'dealer_id' => $validated['dealer_id'],
            'amount'    => $validated['amount'],
            'date'      => $validated['date'],
            'payment_mode' => $validated['payment_mode'],
            'notes'     => $validated['notes'],
            'pending_balance_after' => 0, // Will be updated below
        ]);

        // 2. Fetch the dealer
        $dealer = Dealer::findOrFail($validated['dealer_id']);

        // 3. Deduct the amount
        $dealer->decrement('pending_amount', $validated['amount']);

        // 4. Prevent negative balance
        if ($dealer->fresh()->pending_amount < 0) {
            $dealer->update(['pending_amount' => 0]);
        }

        // Update the payment record with the final balance
        $payment->update(['pending_balance_after' => $dealer->fresh()->pending_amount]);

        return back()->with('success', 'Dealer payment recorded and balance updated.');
    }

    /**
     * Handle payment updates (optional logic if payment is edited).
     */
    public function updateDealerPayment(Request $request, $id)
    {
        $payment = DealerPayment::findOrFail($id);
        $oldAmount = $payment->amount;

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            // other fields...
        ]);

        $diff = $validated['amount'] - $oldAmount;
        
        $payment->update($validated);
        
        $dealer = Dealer::findOrFail($payment->dealer_id);
        $dealer->decrement('pending_amount', $diff);

        if ($dealer->fresh()->pending_amount < 0) {
            $dealer->update(['pending_amount' => 0]);
        }

        return back()->with('success', 'Payment updated and dealer balance adjusted.');
    }
}
