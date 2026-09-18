<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TechnicianCollectorLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        User::create([
            'name' => 'Randi Pratama (Teknisi Lapangan)',
            'email' => 'teknisi@banterpol.net',
            'phone' => '081234567888',
            'role' => 'technician',
            'is_active' => true,
            'password' => Hash::make('teknisi'),
        ]);

        User::create([
            'name' => 'Bayu Saputra (Kolektor Lapangan)',
            'email' => 'kolektor@banterpol.net',
            'phone' => '081234567777',
            'role' => 'collector',
            'is_active' => true,
            'password' => Hash::make('kolektor'),
        ]);

        User::create([
            'name' => 'Mamat (Teknisi Lapangan)',
            'email' => 'mamat@teknisi.net',
            'phone' => '081234567001',
            'role' => 'technician',
            'is_active' => true,
            'password' => Hash::make('mamatteknisi'),
        ]);

        User::create([
            'name' => 'Aji (Teknisi Lapangan)',
            'email' => 'aji@teknisi.net',
            'phone' => '081234567002',
            'role' => 'technician',
            'is_active' => true,
            'password' => Hash::make('ajiteknisi'),
        ]);

        User::create([
            'name' => 'Danu (Teknisi Lapangan)',
            'email' => 'danu@teknisi.net',
            'phone' => '081234567003',
            'role' => 'technician',
            'is_active' => true,
            'password' => Hash::make('danuteknisi'),
        ]);
    }

    public function test_teknisi_can_login_with_full_email(): void
    {
        $response = $this->post('/login', [
            'email' => 'teknisi@banterpol.net',
            'password' => 'teknisi',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('teknisi.dashboard'));
    }

    public function test_teknisi_can_login_with_short_username(): void
    {
        $response = $this->post('/login', [
            'email' => 'teknisi',
            'password' => 'teknisi',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('teknisi.dashboard'));
    }

    public function test_mamat_can_login_with_email_and_username(): void
    {
        $responseEmail = $this->post('/login', [
            'email' => 'mamat@teknisi.net',
            'password' => 'mamatteknisi',
        ]);
        $this->assertAuthenticated();
        $responseEmail->assertRedirect(route('teknisi.dashboard'));

        auth()->logout();

        $responseUser = $this->post('/login', [
            'email' => 'mamat',
            'password' => 'mamatteknisi',
        ]);
        $this->assertAuthenticated();
        $responseUser->assertRedirect(route('teknisi.dashboard'));
    }

    public function test_aji_can_login_with_email_and_username(): void
    {
        $responseEmail = $this->post('/login', [
            'email' => 'aji@teknisi.net',
            'password' => 'ajiteknisi',
        ]);
        $this->assertAuthenticated();
        $responseEmail->assertRedirect(route('teknisi.dashboard'));

        auth()->logout();

        $responseUser = $this->post('/login', [
            'email' => 'aji',
            'password' => 'ajiteknisi',
        ]);
        $this->assertAuthenticated();
        $responseUser->assertRedirect(route('teknisi.dashboard'));
    }

    public function test_danu_can_login_with_email_and_username(): void
    {
        $responseEmail = $this->post('/login', [
            'email' => 'danu@teknisi.net',
            'password' => 'danuteknisi',
        ]);
        $this->assertAuthenticated();
        $responseEmail->assertRedirect(route('teknisi.dashboard'));

        auth()->logout();

        $responseUser = $this->post('/login', [
            'email' => 'danu',
            'password' => 'danuteknisi',
        ]);
        $this->assertAuthenticated();
        $responseUser->assertRedirect(route('teknisi.dashboard'));
    }

    public function test_kolektor_can_login_with_full_email(): void
    {
        $response = $this->post('/login', [
            'email' => 'kolektor@banterpol.net',
            'password' => 'kolektor',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('kolektor.dashboard'));
    }

    public function test_kolektor_can_login_with_short_username(): void
    {
        $response = $this->post('/login', [
            'email' => 'kolektor',
            'password' => 'kolektor',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('kolektor.dashboard'));
    }

    public function test_technician_and_collector_cannot_login_with_wrong_password(): void
    {
        $responseTeknisi = $this->post('/login', [
            'email' => 'teknisi@banterpol.net',
            'password' => 'wrongpassword',
        ]);
        $this->assertGuest();
        $responseTeknisi->assertSessionHasErrors('email');

        $responseMamat = $this->post('/login', [
            'email' => 'mamat@teknisi.net',
            'password' => 'wrongpassword',
        ]);
        $this->assertGuest();
        $responseMamat->assertSessionHasErrors('email');

        $responseKolektor = $this->post('/login', [
            'email' => 'kolektor@banterpol.net',
            'password' => 'wrongpassword',
        ]);
        $this->assertGuest();
        $responseKolektor->assertSessionHasErrors('email');
    }
}
