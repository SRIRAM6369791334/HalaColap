<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vendor_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->date('date');
            $table->decimal('total_amount', 12, 2);
            $table->decimal('cash_amount', 12, 2)->default(0.00);
            $table->decimal('bank_amount', 12, 2)->default(0.00);
            $table->decimal('investment_amount', 12, 2)->default(0.00);
            $table->decimal('adjusted_amount', 12, 2)->default(0.00);
            $table->string('payment_mode')->default('Cash');
            $table->string('bank_transfer_type')->nullable();
            $table->enum('status', ['Pending', 'Partially Adjusted', 'Fully Adjusted'])->default('Pending');
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('vendor_id');
            $table->index('date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_advances');
    }
};
