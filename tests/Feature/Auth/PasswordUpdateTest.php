<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }

    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('updatePassword', 'current_password')
            ->assertRedirect('/profile');
    }

    public function test_google_user_can_set_password_without_current_password_first_time(): void
    {
        $googleUser = User::factory()->create([
            'google_id' => 'google-user-999',
            'avatar' => 'https://lh3.googleusercontent.com/avatar-test.jpg',
            'password_set_at' => null,
        ]);

        $this->assertFalse($googleUser->hasSetPassword());

        // View profile page: Kata Sandi Saat Ini is NOT visible, Google avatar is visible
        $viewResponse = $this->actingAs($googleUser)->get('/profile');
        $viewResponse->assertOk();
        $viewResponse->assertDontSee('Kata Sandi Saat Ini');
        $viewResponse->assertSee('Kata Sandi Baru');
        $viewResponse->assertSee('Konfirmasi Kata Sandi Baru');
        $viewResponse->assertSee('https://lh3.googleusercontent.com/avatar-test.jpg');

        // Set password without current_password
        $updateResponse = $this
            ->actingAs($googleUser)
            ->from('/profile')
            ->put('/password', [
                'password' => 'first-password-123',
                'password_confirmation' => 'first-password-123',
            ]);

        $updateResponse
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $googleUser->refresh();
        $this->assertTrue($googleUser->hasSetPassword());
        $this->assertNotNull($googleUser->password_set_at);
        $this->assertTrue(Hash::check('first-password-123', $googleUser->password));

        // Now, view profile page again: Kata Sandi Saat Ini IS visible!
        $secondViewResponse = $this->actingAs($googleUser)->get('/profile');
        $secondViewResponse->assertOk();
        $secondViewResponse->assertSee('Kata Sandi Saat Ini');

        // Subsequent password change WITHOUT current_password must FAIL
        $failResponse = $this
            ->actingAs($googleUser)
            ->from('/profile')
            ->put('/password', [
                'password' => 'second-password-123',
                'password_confirmation' => 'second-password-123',
            ]);

        $failResponse
            ->assertSessionHasErrorsIn('updatePassword', 'current_password')
            ->assertRedirect('/profile');

        // Subsequent password change WITH correct current_password must SUCCEED
        $successResponse = $this
            ->actingAs($googleUser)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'first-password-123',
                'password' => 'second-password-123',
                'password_confirmation' => 'second-password-123',
            ]);

        $successResponse
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertTrue(Hash::check('second-password-123', $googleUser->refresh()->password));
    }
}
