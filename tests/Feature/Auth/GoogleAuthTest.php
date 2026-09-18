<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirect_to_google_contains_valid_redirect_uri(): void
    {
        $response = $this->get(route('auth.google'));

        $response->assertRedirect();
        $targetUrl = $response->headers->get('Location');

        // Pastikan target URL diarahkan ke Google OAuth
        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/auth', $targetUrl);
        // Pastikan redirect_uri yang dikirim memiliki protokol http:// atau https://
        $this->assertStringContainsString('redirect_uri=http%3A%2F%2F127.0.0.1%3A8000%2Fauth%2Fgoogle%2Fcallback', $targetUrl);
    }

    public function test_google_callback_creates_and_authenticates_new_user(): void
    {
        $mockSocialiteUser = Mockery::mock(SocialiteUser::class);
        $mockSocialiteUser->shouldReceive('getId')->andReturn('google-unique-id-12345');
        $mockSocialiteUser->shouldReceive('getName')->andReturn('Test Google User');
        $mockSocialiteUser->shouldReceive('getNickname')->andReturn('testuser');
        $mockSocialiteUser->shouldReceive('getEmail')->andReturn('googleuser@test.com');
        $mockSocialiteUser->shouldReceive('getAvatar')->andReturn('https://lh3.googleusercontent.com/avatar.jpg');

        $provider = Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $provider->shouldReceive('user')->andReturn($mockSocialiteUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $this->assertAuthenticated();
        $user = User::where('email', 'googleuser@test.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('google-unique-id-12345', $user->google_id);
        $this->assertEquals('customer', $user->role);
        $response->assertRedirect(route('home', absolute: false));
    }

    public function test_google_callback_links_existing_user_by_email(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'existing@test.com',
            'google_id' => null,
            'role' => 'customer',
        ]);

        $mockSocialiteUser = Mockery::mock(SocialiteUser::class);
        $mockSocialiteUser->shouldReceive('getId')->andReturn('google-id-existing');
        $mockSocialiteUser->shouldReceive('getName')->andReturn('Existing Name');
        $mockSocialiteUser->shouldReceive('getNickname')->andReturn(null);
        $mockSocialiteUser->shouldReceive('getEmail')->andReturn('existing@test.com');
        $mockSocialiteUser->shouldReceive('getAvatar')->andReturn('https://lh3.googleusercontent.com/avatar2.jpg');

        $provider = Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $provider->shouldReceive('user')->andReturn($mockSocialiteUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $this->assertAuthenticatedAs($existingUser);
        $existingUser->refresh();
        $this->assertEquals('google-id-existing', $existingUser->google_id);
    }

    public function test_google_callback_recovers_from_invalid_state_exception(): void
    {
        $mockSocialiteUser = Mockery::mock(SocialiteUser::class);
        $mockSocialiteUser->shouldReceive('getId')->andReturn('google-id-stateless');
        $mockSocialiteUser->shouldReceive('getName')->andReturn('Stateless User');
        $mockSocialiteUser->shouldReceive('getNickname')->andReturn(null);
        $mockSocialiteUser->shouldReceive('getEmail')->andReturn('stateless@test.com');
        $mockSocialiteUser->shouldReceive('getAvatar')->andReturn(null);

        $provider = Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $provider->shouldReceive('user')->once()->andThrow(new \Laravel\Socialite\Two\InvalidStateException());
        $provider->shouldReceive('stateless')->once()->andReturnSelf();
        $provider->shouldReceive('user')->once()->andReturn($mockSocialiteUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $this->assertAuthenticated();
        $user = User::where('email', 'stateless@test.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('google-id-stateless', $user->google_id);
    }
}
