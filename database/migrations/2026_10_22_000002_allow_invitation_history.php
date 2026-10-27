<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_user_invitations', function (Blueprint $table) {
            $table->dropUnique('tenant_invitation_email_unique');
            $table->dropUnique('tenant_invitation_username_unique');
            $table->index(['tenant_id', 'email'], 'tenant_invitation_email_index');
            $table->index(['tenant_id', 'username'], 'tenant_invitation_username_index');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_user_invitations', function (Blueprint $table) {
            $table->dropIndex('tenant_invitation_email_index');
            $table->dropIndex('tenant_invitation_username_index');
            $table->unique(['tenant_id', 'email'], 'tenant_invitation_email_unique');
            $table->unique(['tenant_id', 'username'], 'tenant_invitation_username_unique');
        });
    }
};
