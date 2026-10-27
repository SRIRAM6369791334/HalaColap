<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('firm_name');
            $table->string('gst_number', 20)->nullable();
            $table->string('location')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('phone', 20);
            $table->string('route')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('firm_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
