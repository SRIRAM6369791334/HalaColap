<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds team_id to Spatie Permission tables (roles, model_has_roles, model_has_permissions)
     * to enable multi-tenant RBAC under Spatie Teams mode.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');

        $teamKey = $columnNames['team_foreign_key'] ?? 'team_id';
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';
        $modelMorphKey = $columnNames['model_morph_key'] ?? 'model_id';

        $isSqlite = DB::getDriverName() === 'sqlite';

        // Ensure baseline Tenant #1 exists in tenants table so foreign keys satisfy referential integrity
        if (Schema::hasTable('tenants')) {
            DB::table('tenants')->insertOrIgnore([
                'id' => 1,
                'name' => 'MSR Broilers',
                'slug' => 'msr-broilers',
                'company_name' => 'MSR Broilers',
                'email' => 'admin@poultry.com',
                'currency' => 'INR',
                'timezone' => 'Asia/Kolkata',
                'date_format' => 'd-m-Y',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // =========================================================================
        // 1. ROLES TABLE
        // =========================================================================
        if (! Schema::hasColumn($tableNames['roles'], $teamKey)) {
            Schema::table($tableNames['roles'], function (Blueprint $table) use ($teamKey) {
                // Add team_id column (nullable to permit global/system roles if ever needed)
                $table->unsignedBigInteger($teamKey)->nullable()->after('id');
            });

            // Backfill existing roles to Tenant #1 (MSR Broilers)
            DB::table($tableNames['roles'])
                ->whereNull($teamKey)
                ->update([$teamKey => 1]);

            Schema::table($tableNames['roles'], function (Blueprint $table) use ($tableNames, $teamKey, $isSqlite) {
                // Drop single-tenant unique constraint: UNIQUE(name, guard_name)
                $table->dropUnique('roles_name_guard_name_unique');

                // Create tenant-scoped unique constraint: UNIQUE(team_id, name, guard_name)
                $table->unique([$teamKey, 'name', 'guard_name'], 'roles_team_name_guard_name_unique');

                // Index on team_id
                $table->index($teamKey, 'roles_team_foreign_key_index');

                // Add foreign key constraint to tenants table if present
                if (Schema::hasTable('tenants') && ! $isSqlite) {
                    $table->foreign($teamKey)
                        ->references('id')
                        ->on('tenants')
                        ->onDelete('cascade');
                }
            });
        }

        // =========================================================================
        // 2. MODEL_HAS_PERMISSIONS TABLE
        // =========================================================================
        if (! Schema::hasColumn($tableNames['model_has_permissions'], $teamKey)) {
            Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) use ($tableNames, $teamKey, $pivotPermission, $modelMorphKey, $isSqlite) {
                // Add team_id column with default 1 for existing records
                $table->unsignedBigInteger($teamKey)->default(1);
                $table->index($teamKey, 'model_has_permissions_team_foreign_key_index');

                if (! $isSqlite) {
                    // Must drop FK before dropping composite primary key in MySQL
                    $table->dropForeign([$pivotPermission]);
                }

                $table->dropPrimary();

                // New composite primary key: (team_id, permission_id, model_id, model_type)
                $table->primary(
                    [$teamKey, $pivotPermission, $modelMorphKey, 'model_type'],
                    'model_has_permissions_permission_model_type_primary'
                );

                if (! $isSqlite) {
                    // Re-add FK to permissions table
                    $table->foreign($pivotPermission)
                        ->references('id')
                        ->on($tableNames['permissions'])
                        ->onDelete('cascade');

                    // Add FK to tenants table if present
                    if (Schema::hasTable('tenants')) {
                        $table->foreign($teamKey)
                            ->references('id')
                            ->on('tenants')
                            ->onDelete('cascade');
                    }
                }
            });

            // Ensure any existing rows are stamped with team_id = 1
            DB::table($tableNames['model_has_permissions'])
                ->whereNull($teamKey)
                ->orWhere($teamKey, 0)
                ->update([$teamKey => 1]);
        }

        // =========================================================================
        // 3. MODEL_HAS_ROLES TABLE
        // =========================================================================
        if (! Schema::hasColumn($tableNames['model_has_roles'], $teamKey)) {
            Schema::table($tableNames['model_has_roles'], function (Blueprint $table) use ($tableNames, $teamKey, $pivotRole, $modelMorphKey, $isSqlite) {
                // Add team_id column with default 1 for existing records
                $table->unsignedBigInteger($teamKey)->default(1);
                $table->index($teamKey, 'model_has_roles_team_foreign_key_index');

                if (! $isSqlite) {
                    // Must drop FK before dropping composite primary key in MySQL
                    $table->dropForeign([$pivotRole]);
                }

                $table->dropPrimary();

                // New composite primary key: (team_id, role_id, model_id, model_type)
                $table->primary(
                    [$teamKey, $pivotRole, $modelMorphKey, 'model_type'],
                    'model_has_roles_role_model_type_primary'
                );

                if (! $isSqlite) {
                    // Re-add FK to roles table
                    $table->foreign($pivotRole)
                        ->references('id')
                        ->on($tableNames['roles'])
                        ->onDelete('cascade');

                    // Add FK to tenants table if present
                    if (Schema::hasTable('tenants')) {
                        $table->foreign($teamKey)
                            ->references('id')
                            ->on('tenants')
                            ->onDelete('cascade');
                    }
                }
            });

            // Ensure any existing rows are stamped with team_id = 1
            DB::table($tableNames['model_has_roles'])
                ->whereNull($teamKey)
                ->orWhere($teamKey, 0)
                ->update([$teamKey => 1]);
        }

        // =========================================================================
        // 4. RESET CACHE
        // =========================================================================
        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    /**
     * Reverse the migrations.
     *
     * Fully reversible rollback restoring single-tenant schema.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');

        $teamKey = $columnNames['team_foreign_key'] ?? 'team_id';
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';
        $modelMorphKey = $columnNames['model_morph_key'] ?? 'model_id';

        $isSqlite = DB::getDriverName() === 'sqlite';

        // 1. REVERSE MODEL_HAS_ROLES
        if (Schema::hasColumn($tableNames['model_has_roles'], $teamKey)) {
            // Delete non-tenant-1 assignments to prevent duplicate key error during primary key collapse
            DB::table($tableNames['model_has_roles'])->where($teamKey, '!=', 1)->delete();

            Schema::table($tableNames['model_has_roles'], function (Blueprint $table) use ($tableNames, $teamKey, $pivotRole, $modelMorphKey, $isSqlite) {
                if (! $isSqlite) {
                    if (Schema::hasTable('tenants')) {
                        $table->dropForeign([$teamKey]);
                    }
                    $table->dropForeign([$pivotRole]);
                }

                $table->dropPrimary();

                $table->primary(
                    [$pivotRole, $modelMorphKey, 'model_type'],
                    'model_has_roles_role_model_type_primary'
                );

                if (! $isSqlite) {
                    $table->foreign($pivotRole)
                        ->references('id')
                        ->on($tableNames['roles'])
                        ->onDelete('cascade');
                }

                $table->dropIndex('model_has_roles_team_foreign_key_index');
                $table->dropColumn($teamKey);
            });
        }

        // 2. REVERSE MODEL_HAS_PERMISSIONS
        if (Schema::hasColumn($tableNames['model_has_permissions'], $teamKey)) {
            // Delete non-tenant-1 permissions to prevent duplicate key error
            DB::table($tableNames['model_has_permissions'])->where($teamKey, '!=', 1)->delete();

            Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) use ($tableNames, $teamKey, $pivotPermission, $modelMorphKey, $isSqlite) {
                if (! $isSqlite) {
                    if (Schema::hasTable('tenants')) {
                        $table->dropForeign([$teamKey]);
                    }
                    $table->dropForeign([$pivotPermission]);
                }

                $table->dropPrimary();

                $table->primary(
                    [$pivotPermission, $modelMorphKey, 'model_type'],
                    'model_has_permissions_permission_model_type_primary'
                );

                if (! $isSqlite) {
                    $table->foreign($pivotPermission)
                        ->references('id')
                        ->on($tableNames['permissions'])
                        ->onDelete('cascade');
                }

                $table->dropIndex('model_has_permissions_team_foreign_key_index');
                $table->dropColumn($teamKey);
            });
        }

        // 3. REVERSE ROLES
        if (Schema::hasColumn($tableNames['roles'], $teamKey)) {
            // Delete any roles created for other tenants to prevent unique constraint collision on name
            DB::table($tableNames['roles'])->where($teamKey, '!=', 1)->delete();

            Schema::table($tableNames['roles'], function (Blueprint $table) use ($teamKey, $isSqlite) {
                if (! $isSqlite && Schema::hasTable('tenants')) {
                    $table->dropForeign([$teamKey]);
                }

                $table->dropUnique('roles_team_name_guard_name_unique');
                $table->dropIndex('roles_team_foreign_key_index');

                // Restore single-tenant unique constraint
                $table->unique(['name', 'guard_name'], 'roles_name_guard_name_unique');

                $table->dropColumn($teamKey);
            });
        }

        // 4. RESET CACHE
        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }
};
