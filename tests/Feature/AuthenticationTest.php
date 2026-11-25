<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Tests require an isolated in-memory SQLite database.');
        }
    }

    public function test_login_restores_identity_and_logout_ends_session(): void
    {
        $user = User::factory()->create(['password' => 'correct-password']);
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertUnauthorized();
        $this->assertGuest();
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'correct-password'])
            ->assertOk()->assertJsonPath('id', $user->id)->assertJsonStructure(['csrf_token']);
        $this->assertAuthenticatedAs($user);
        $this->getJson('/api/me')->assertOk()->assertJsonPath('email', $user->email);
        $this->postJson('/api/logout')->assertOk();
        $this->assertGuest();
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_ticket_endpoints_require_a_session(): void
    {
        $this->getJson('/api/tickets')->assertUnauthorized();
        $this->getJson('/api/agents')->assertUnauthorized();
    }

    public function test_users_cannot_spoof_ticket_list_identity(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user)->getJson('/api/tickets?user_id=999')->assertOk()->assertExactJson([]);
    }

    public function test_failed_logins_are_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', ['email' => 'missing@example.com', 'password' => 'wrong'])
                ->assertUnauthorized();
        }
        $this->postJson('/api/login', ['email' => 'missing@example.com', 'password' => 'wrong'])
            ->assertStatus(429);
    }
}
