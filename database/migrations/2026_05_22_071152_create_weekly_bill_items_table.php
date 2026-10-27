<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('weekly_bill_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weekly_bill_id')->constrained()->cascadeOnDelete();
            $table->string('item_name');
            $table->decimal('quantity_kg', 10, 2);
            $table->decimal('rate_per_kg', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->timestamps();
        });

        // Migrate existing flat data from weekly_bills to weekly_bill_items
        $bills = DB::table('weekly_bills')->get();
        foreach ($bills as $bill) {
            $qty = $bill->quantity_kg ?: 0;
            $amount = $bill->amount ?: 0;
            $rate = $qty > 0 ? round($amount / $qty, 2) : $amount;
            $tax = $bill->gst_amount ?? 0;

            DB::table('weekly_bill_items')->insert([
                'weekly_bill_id' => $bill->id,
                'item_name'      => $bill->items_description ?: 'Weekly Settlement',
                'quantity_kg'    => $qty,
                'rate_per_kg'    => $rate,
                'tax_amount'     => $tax,
                'total_amount'   => $amount + $tax,
                'created_at'     => $bill->created_at,
                'updated_at'     => $bill->updated_at,
            ]);
        }

        // Drop obsolete columns from weekly_bills
        Schema::table('weekly_bills', function (Blueprint $table) {
            $table->dropColumn(['items_description', 'quantity_kg']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weekly_bills', function (Blueprint $table) {
            $table->string('items_description')->nullable();
            $table->decimal('quantity_kg', 10, 2)->nullable();
        });

        Schema::dropIfExists('weekly_bill_items');
    }
};
