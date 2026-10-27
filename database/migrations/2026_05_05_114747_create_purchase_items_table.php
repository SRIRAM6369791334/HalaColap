<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->onDelete('cascade');
            $table->string('item_name');
            $table->decimal('quantity', 10, 2);
            $table->string('unit')->default('kg');
            $table->decimal('rate', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->timestamps();
        });

        // Migrate existing data
        $purchases = DB::table('purchases')->get();
        foreach ($purchases as $purchase) {
            DB::table('purchase_items')->insert([
                'purchase_id' => $purchase->id,
                'item_name' => $purchase->item ?? 'Unknown',
                'quantity' => $purchase->quantity ?? 0,
                'unit' => $purchase->unit ?? 'kg',
                'rate' => $purchase->rate ?? 0,
                'tax_amount' => $purchase->gst_amount ?? 0,
                'total_amount' => $purchase->total_amount ?? 0,
                'created_at' => $purchase->created_at,
                'updated_at' => $purchase->updated_at,
            ]);
        }

        // Now remove item specific columns from purchases table
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex(['item']);
            $table->dropColumn(['item', 'quantity', 'unit', 'rate', 'gst_amount']);
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('item')->nullable();
            $table->decimal('quantity', 10, 2)->nullable();
            $table->string('unit')->nullable();
            $table->decimal('rate', 10, 2)->nullable();
            $table->decimal('gst_amount', 10, 2)->nullable();
            $table->index('item');
        });

        // Restore data if possible (only for the first item of each purchase)
        $items = DB::table('purchase_items')->get()->groupBy('purchase_id');
        foreach ($items as $purchaseId => $purchaseItems) {
            $firstItem = $purchaseItems->first();
            DB::table('purchases')->where('id', $purchaseId)->update([
                'item' => $firstItem->item_name,
                'quantity' => $firstItem->quantity,
                'unit' => $firstItem->unit,
                'rate' => $firstItem->rate,
                'gst_amount' => $firstItem->tax_amount,
            ]);
        }

        Schema::dropIfExists('purchase_items');
    }
};
