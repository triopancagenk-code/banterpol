<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_direktur_can_authenticate_with_username_and_access_admin(): void
    {
        $direktur = User::create([
            'email' => 'direktur@banterpool.net',
            'name' => 'Direktur Utama Banterpool',
            'role' => 'admin',
            'is_active' => true,
            'password' => bcrypt('direktur'),
        ]);

        $response = $this->post('/login', [
            'email' => 'direktur',
            'password' => 'direktur',
        ]);

        $this->assertAuthenticatedAs($direktur);
        $response->assertRedirect(route('admin.dashboard', absolute: false));

        // Ensure direktur can access admin dashboard without 403
        $adminResponse = $this->actingAs($direktur)->get(route('admin.dashboard'));
        $adminResponse->assertStatus(200);
    }
}
