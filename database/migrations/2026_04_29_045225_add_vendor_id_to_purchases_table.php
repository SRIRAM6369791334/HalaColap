<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Vendor;
use App\Models\Purchase;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->unsignedBigInteger('vendor_id')->nullable()->after('id');
            $table->foreign('vendor_id')->references('id')->on('vendors')->nullOnDelete();
        });

        // ✅ Migrate existing vendor_name → vendor_id (one-time data fix)
        Purchase::all()->each(function ($purchase) {
            $vendor = Vendor::where('firm_name', $purchase->vendor_name)->first();
            if ($vendor) {
                $purchase->update(['vendor_id' => $vendor->id]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
            $table->dropColumn('vendor_id');
        });
    }
};
