<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_redirect_redirects_to_google(): void
    {
        $response = $this->get('/auth-google-redirect');
        $response->assertStatus(302);
        $this->assertStringContainsString('accounts.google.com', $response->headers->get('Location') ?? '');
    }

    public function test_google_callback_creates_and_logs_in_customer(): void
    {
        $abstractUser = Mockery::mock(SocialiteUser::class);
        $abstractUser->shouldReceive('getId')->andReturn('google-id-12345');
        $abstractUser->shouldReceive('getEmail')->andReturn('customer@example.com');
        $abstractUser->shouldReceive('getName')->andReturn('Budi Santoso');
        $abstractUser->shouldReceive('getNickname')->andReturn('Budi');
        $abstractUser->shouldReceive('getAvatar')->andReturn('https://lh3.googleusercontent.com/avatar.jpg');

        $provider = Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $provider->shouldReceive('user')->andReturn($abstractUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get('/auth-google-callback');

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'email' => 'customer@example.com',
            'google_id' => 'google-id-12345',
            'role' => 'customer',
        ]);
    }

    public function test_google_callback_redirects_admin_to_admin_dashboard(): void
    {
        User::factory()->create([
            'email' => 'admin@banterpool.net',
            'role' => 'admin',
            'google_id' => 'admin-google-id',
        ]);

        $abstractUser = Mockery::mock(SocialiteUser::class);
        $abstractUser->shouldReceive('getId')->andReturn('admin-google-id');
        $abstractUser->shouldReceive('getEmail')->andReturn('admin@banterpool.net');
        $abstractUser->shouldReceive('getName')->andReturn('Admin Banterpool');
        $abstractUser->shouldReceive('getNickname')->andReturn('Admin');
        $abstractUser->shouldReceive('getAvatar')->andReturn(null);

        $provider = Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $provider->shouldReceive('user')->andReturn($abstractUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get('/auth-google-callback');

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_google_redirect_uses_official_callback_url(): void
    {
        $response = $this->get('/auth-google-redirect');
        $response->assertStatus(302);
        
        $location = urldecode($response->headers->get('Location') ?? '');
        $this->assertStringContainsString('https://banterpol.sagainfra.id/auth/google/callback', $location);
    }

    public function test_auth_google_callback_route_works(): void
    {
        $abstractUser = Mockery::mock(SocialiteUser::class);
        $abstractUser->shouldReceive('getId')->andReturn('google-id-5678');
        $abstractUser->shouldReceive('getEmail')->andReturn('user5678@example.com');
        $abstractUser->shouldReceive('getName')->andReturn('User Test');
        $abstractUser->shouldReceive('getNickname')->andReturn('User');
        $abstractUser->shouldReceive('getAvatar')->andReturn(null);

        $provider = Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $provider->shouldReceive('user')->andReturn($abstractUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();
    }
}
