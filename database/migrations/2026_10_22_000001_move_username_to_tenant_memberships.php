<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_user', function (Blueprint $table) {
            $table->string('username', 100)->nullable()->after('user_id');
        });

        $used = [];
        DB::table('tenant_user')
            ->join('users', 'users.id', '=', 'tenant_user.user_id')
            ->select('tenant_user.id', 'tenant_user.tenant_id', 'tenant_user.user_id', 'users.username')
            ->orderBy('tenant_user.id')
            ->get()
            ->each(function ($membership) use (&$used) {
                $base = mb_strtolower(trim((string) ($membership->username ?: 'user'.$membership->user_id)));
                $base = preg_replace('/[^a-z0-9_.-]+/', '_', $base) ?: 'user'.$membership->user_id;
                $candidate = mb_substr($base, 0, 100);
                $key = $membership->tenant_id.':'.$candidate;
                $suffix = 1;

                while (isset($used[$key])) {
                    $tail = '_'.$suffix++;
                    $candidate = mb_substr($base, 0, 100 - strlen($tail)).$tail;
                    $key = $membership->tenant_id.':'.$candidate;
                }

                $used[$key] = true;
                DB::table('tenant_user')->where('id', $membership->id)->update(['username' => $candidate]);
            });

        Schema::table('tenant_user', function (Blueprint $table) {
            $table->string('username', 100)->nullable(false)->change();
            $table->unique(['tenant_id', 'username'], 'tenant_user_tenant_username_unique');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_username_unique');
            $table->dropColumn('username');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->default(null)->change();
        });

        Schema::create('tenant_user_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('email');
            $table->string('username', 100);
            $table->string('role', 50);
            $table->string('token_hash', 64)->unique();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'email'], 'tenant_invitation_email_unique');
            $table->unique(['tenant_id', 'username'], 'tenant_invitation_username_unique');
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_user_invitations');

        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 100)->nullable()->after('name');
        });

        DB::table('users')->orderBy('id')->get()->each(function ($user) {
            $username = DB::table('tenant_user')
                ->where('user_id', $user->id)
                ->orderBy('id')
                ->value('username');
            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        });

        Schema::table('tenant_user', function (Blueprint $table) {
            $table->dropUnique('tenant_user_tenant_username_unique');
            $table->dropColumn('username');
        });
    }
};
