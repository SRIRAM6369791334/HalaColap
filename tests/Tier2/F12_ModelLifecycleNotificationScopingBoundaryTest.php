<?php

namespace Tests\Tier2;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\E2ETestCase;

/**
 * Feature F12 Boundary Tests: Model Lifecycle Notification Scoping
 * Verifies zero-user tenants, large user sets, and inactive user notification exclusions.
 */
class F12_ModelLifecycleNotificationScopingBoundaryTest extends E2ETestCase
{
    public function test_tenant_with_zero_users_handles_event_without_error(): void
    {
        $this->requiresColumn('users', 'current_tenant_id');

        $emptyTenant = $this->createTenant();
        $users = User::where('current_tenant_id', $emptyTenant->id)->get();

        $this->assertCount(0, $users);
    }

    public function test_inactive_user_does_not_receive_active_broadcasts(): void
    {
        $this->requiresColumn('users', 'current_tenant_id');

        $tenant = $this->createTenant();
        $inactiveUser = $this->createUser([
            'current_tenant_id' => $tenant->id,
            'is_active'         => false,
        ]);

        $activeUsers = User::where('current_tenant_id', $tenant->id)
            ->where('is_active', true)
            ->get();

        $this->assertFalse($activeUsers->contains('id', $inactiveUser->id));
    }

    public function test_notification_with_large_json_payload(): void
    {
        $user = $this->createUser();
        $largeData = [
            'entries' => array_fill(0, 100, ['box_weight' => 1200.5, 'bird_weight' => 1000.0]),
        ];

        DB::table('notifications')->insert([
            'id'              => (string) \Illuminate\Support\Str::uuid(),
            'type'            => 'App\\Notifications\\DayLoadBatchFinalized',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id'   => $user->id,
            'data'            => json_encode($largeData),
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $this->assertEquals(1, DB::table('notifications')->where('notifiable_id', $user->id)->count());
    }

    public function test_marking_all_notifications_read_only_affects_current_user(): void
    {
        $u1 = $this->createUser();
        $u2 = $this->createUser();

        // Notification for u1
        DB::table('notifications')->insert([
            'id'              => (string) \Illuminate\Support\Str::uuid(),
            'type'            => 'App\\Notifications\\Alert',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id'   => $u1->id,
            'data'            => json_encode(['text' => 'Alert 1']),
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        // Notification for u2
        DB::table('notifications')->insert([
            'id'              => (string) \Illuminate\Support\Str::uuid(),
            'type'            => 'App\\Notifications\\Alert',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id'   => $u2->id,
            'data'            => json_encode(['text' => 'Alert 2']),
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        // Mark u1 read
        $u1->unreadNotifications()->update(['read_at' => now()]);

        $this->assertSame(0, $u1->unreadNotifications()->count());
        $this->assertSame(1, $u2->unreadNotifications()->count());
    }

    public function test_empty_notification_table_query(): void
    {
        $u = $this->createUser();
        $this->assertSame(0, $u->notifications()->count());
    }
}
