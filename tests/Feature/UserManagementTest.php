<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Tests require an isolated in-memory SQLite database.');
        }
    }

    private function account(array $overrides = []): array
    {
        return array_merge([
            'name' => 'New Developer', 'email' => 'new@example.com', 'role' => 'agent',
            'password' => 'long-test-password', 'password_confirmation' => 'long-test-password',
            'is_active' => 1, 'permissions' => ['tickets.view', 'tickets.comment'],
        ], $overrides);
    }

    public function test_only_administrators_can_manage_users(): void
    {
        $this->get('/admin/users')->assertRedirect('/login');
        $developer = User::factory()->create(['role' => 'agent']);
        $this->actingAs($developer)->get('/admin/users')->assertForbidden();
        $this->post('/admin/users', $this->account())->assertForbidden();
    }

    public function test_administrator_creates_account_with_hashed_password_and_permissions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/admin/users')->assertOk()->assertSee('User Management');
        $this->post('/admin/users', $this->account())->assertRedirect(route('admin.users.index'));
        $user = User::where('email', 'new@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('long-test-password', $user->password));
        $this->assertTrue($user->canAccess('tickets.view'));
        $this->assertFalse($user->canAccess('tickets.assign'));
        $this->get(route('admin.users.edit', $user))->assertOk();
        $this->actingAs($user)->getJson('/api/tickets')->assertOk();
        $this->getJson('/api/analytics')->assertForbidden();
        $this->postJson('/api/tickets', [])->assertForbidden();
        $category = \App\Models\Category::create(['name' => 'Support']);
        $ticket = \App\Models\Ticket::create([
            'ticket_number' => 'TEST-001', 'user_id' => $user->id, 'category_id' => $category->id,
            'title' => 'Permission test', 'description' => 'Check access', 'status' => 'open', 'priority' => 'low',
        ]);
        $this->patchJson('/api/tickets/'.$ticket->id.'/assign', [])->assertForbidden();
        $this->patchJson('/api/tickets/'.$ticket->id.'/status', ['status' => 'closed'])->assertForbidden();
    }

    public function test_permissions_can_be_revoked_and_account_deactivated(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'agent']);
        $this->actingAs($admin)->put(route('admin.users.update', $user), $this->account([
            'email' => $user->email, 'permissions' => [], 'password' => '', 'password_confirmation' => '',
        ]))->assertRedirect();
        $this->actingAs($user->fresh())->getJson('/api/tickets')->assertForbidden();
        $this->actingAs($admin)->put(route('admin.users.update', $user), $this->account([
            'email' => $user->email, 'is_active' => 0,
        ]))->assertRedirect();
        $this->actingAs($user->fresh())->getJson('/api/me')->assertForbidden();
        $this->assertGuest();
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'long-test-password'])->assertUnauthorized();
    }

    public function test_administrator_cannot_lock_out_own_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->put(route('admin.users.update', $admin), $this->account([
            'email' => $admin->email, 'role' => 'user', 'is_active' => 0,
        ]))->assertSessionHasErrors('role');
        $this->assertSame('admin', $admin->fresh()->role);
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_validation_rejects_duplicate_email_and_unknown_permission(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post('/admin/users', $this->account([
            'email' => $admin->email, 'permissions' => ['users.manage'],
        ]))->assertSessionHasErrors(['email', 'permissions.0']);
    }

    public function test_client_cannot_receive_staff_permissions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post('/admin/users', $this->account([
            'role' => 'user', 'permissions' => ['tickets.view', 'tickets.assign', 'analytics.view'],
        ]))->assertRedirect();
        $user = User::where('email', 'new@example.com')->firstOrFail();
        $this->assertSame(['tickets.view'], $user->effectivePermissions());
    }
}
