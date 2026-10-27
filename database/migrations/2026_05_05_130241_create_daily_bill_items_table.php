<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_bill_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_bill_id')->constrained()->cascadeOnDelete();
            $table->string('item_name');
            $table->decimal('quantity_kg', 10, 2);
            $table->decimal('rate_per_kg', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->timestamps();
        });

        // Migrate existing data from daily_bills to daily_bill_items
        $bills = DB::table('daily_bills')->get();
        foreach ($bills as $bill) {
            DB::table('daily_bill_items')->insert([
                'daily_bill_id' => $bill->id,
                'item_name'     => $bill->items_description ?: 'Poultry Birds',
                'quantity_kg'   => $bill->quantity_kg ?: 0,
                'rate_per_kg'   => $bill->rate_per_kg ?: 0,
                'tax_amount'    => $bill->gst_amount ?: 0,
                'total_amount'  => $bill->amount ?: 0,
                'created_at'    => $bill->created_at,
                'updated_at'    => $bill->updated_at,
            ]);
        }

        // Clean up daily_bills table
        Schema::table('daily_bills', function (Blueprint $table) {
            $table->dropColumn(['items_description', 'quantity_kg', 'rate_per_kg']);
        });
    }

    public function down(): void
    {
        Schema::table('daily_bills', function (Blueprint $table) {
            $table->string('items_description')->nullable();
            $table->decimal('quantity_kg', 10, 2)->nullable();
            $table->decimal('rate_per_kg', 10, 2)->nullable();
        });

        Schema::dropIfExists('daily_bill_items');
    }
};
