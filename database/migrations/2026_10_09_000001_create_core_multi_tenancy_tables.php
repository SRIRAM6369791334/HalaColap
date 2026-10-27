<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create resellers table
        Schema::create('resellers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('email')->unique();
            $table->string('phone', 25)->nullable();
            $table->decimal('commission_rate', 5, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
        });

        // 2. Create tenants table
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 100)->unique();
            $table->string('domain')->nullable()->unique();
            $table->string('company_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 25)->nullable();
            $table->text('address')->nullable();
            $table->string('gst_number', 25)->nullable();
            $table->string('logo_path')->nullable();
            $table->string('currency', 10)->default('INR');
            $table->string('timezone', 50)->default('Asia/Kolkata');
            $table->string('date_format', 20)->default('d-m-Y');
            $table->boolean('is_active')->default(true);
            $table->foreignId('reseller_id')->nullable()->constrained('resellers')->nullOnDelete();
            $table->json('settings')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
            $table->index('reseller_id');
        });

        // 3. Create tenant_user (memberships) pivot table
        Schema::create('tenant_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 50)->default('staff');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id']);
            $table->index('tenant_id');
            $table->index('user_id');
            $table->index('role');
        });

        // 4. Alter users table to add current_tenant_id and is_super_admin
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('current_tenant_id')
                ->nullable()
                ->after('id')
                ->constrained('tenants')
                ->nullOnDelete();

            $table->boolean('is_super_admin')
                ->default(false)
                ->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse in exact inverse dependency order
        if (Schema::hasColumn('users', 'current_tenant_id')) {
            Schema::table('users', function (Blueprint $table) {
                if (DB::getDriverName() !== 'sqlite') {
                    $table->dropForeign(['current_tenant_id']);
                }
                $table->dropColumn(['current_tenant_id', 'is_super_admin']);
            });
        }

        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('tenants');
        Schema::dropIfExists('resellers');
    }
};
