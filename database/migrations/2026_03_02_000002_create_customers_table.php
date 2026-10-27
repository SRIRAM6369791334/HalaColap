<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 20);
            $table->string('address')->nullable();
            $table->string('gst_number', 20)->nullable();
            $table->string('route')->nullable();
            $table->enum('type', ['Retail', 'Wholesale'])->default('Retail');
            $table->decimal('balance', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index('name');
            $table->index('route');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
