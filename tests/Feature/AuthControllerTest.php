<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_a_token_for_valid_credentials(): void
    {
        User::factory()->create(['email' => 'demo@example.com', 'password' => 'secret-password']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'demo@example.com',
            'password' => 'secret-password',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['token']);
    }

    public function test_login_returns_401_for_invalid_password(): void
    {
        User::factory()->create(['email' => 'demo@example.com', 'password' => 'secret-password']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'demo@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_returns_401_for_unknown_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'missing@example.com',
            'password' => 'secret-password',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_validates_required_fields(): void
    {
        $this->postJson('/api/v1/auth/login', [])->assertStatus(422);
    }

    public function test_protected_route_rejects_unauthenticated_request(): void
    {
        $this->getJson('/api/v1/vehicles')->assertStatus(401);
    }

    /**
     * Regression test: getJson() always sends Accept: application/json, which
     * masked a real bug — a plain request without that header (any browser
     * navigation, curl by default) hit Laravel's default "redirect guests to
     * the login route" behavior. This app has no named 'login' route (it's
     * API-only), so that crashed with a 500 (RouteNotFoundException) instead
     * of a 401, until bootstrap/app.php explicitly disabled the redirect.
     */
    public function test_protected_route_returns_401_not_500_for_a_plain_unauthenticated_request(): void
    {
        $this->get('/api/v1/vehicles')->assertStatus(401);
    }

    public function test_issued_token_grants_access_to_protected_routes(): void
    {
        User::factory()->create(['email' => 'demo@example.com', 'password' => 'secret-password']);

        $token = $this->postJson('/api/v1/auth/login', [
            'email' => 'demo@example.com',
            'password' => 'secret-password',
        ])->json('token');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/vehicles')
            ->assertOk();
    }

    public function test_me_returns_the_authenticated_user(): void
    {
        $user = User::factory()->create(['email' => 'demo@example.com', 'password' => 'secret-password']);

        $token = $this->postJson('/api/v1/auth/login', [
            'email' => 'demo@example.com',
            'password' => 'secret-password',
        ])->json('token');

        $response = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/auth/me');

        $response->assertOk();
        $response->assertJsonPath('data.id', $user->id);
        $response->assertJsonPath('data.email', 'demo@example.com');
    }

    public function test_logout_revokes_the_current_token(): void
    {
        User::factory()->create(['email' => 'demo@example.com', 'password' => 'secret-password']);

        $token = $this->postJson('/api/v1/auth/login', [
            'email' => 'demo@example.com',
            'password' => 'secret-password',
        ])->json('token');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout')
            ->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
