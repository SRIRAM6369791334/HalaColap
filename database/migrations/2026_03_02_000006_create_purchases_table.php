<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('vendor_name');
            $table->date('date');
            $table->string('item');
            $table->decimal('quantity', 10, 2);
            $table->string('unit', 20)->default('kg');
            $table->decimal('rate', 10, 2);
            $table->decimal('gst_percentage', 5, 2)->default(18);
            $table->decimal('gst_amount', 10, 2);
            $table->decimal('total_amount', 12, 2);
            $table->enum('payment_mode', ['NEFT', 'Cheque', 'UPI', 'Cash']);
            $table->timestamps();
            $table->index('date');
            $table->index('item');
            $table->index('vendor_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
