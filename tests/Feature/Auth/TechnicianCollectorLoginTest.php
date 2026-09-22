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

        User::create([
            'name' => 'Okta (Teknisi Lapangan)',
            'email' => 'okta@teknisi.net',
            'phone' => '081234567004',
            'role' => 'technician',
            'is_active' => true,
            'password' => Hash::make('oktateknisi'),
        ]);

        User::create([
            'name' => 'Dila (Kolektor Lapangan)',
            'email' => 'dila@kolektor.net',
            'phone' => '081234567101',
            'role' => 'collector',
            'is_active' => true,
            'password' => Hash::make('dilakolektor'),
        ]);

        User::create([
            'name' => 'Saefudin (Kolektor Lapangan)',
            'email' => 'saefudin@kolektor.net',
            'phone' => '081234567102',
            'role' => 'collector',
            'is_active' => true,
            'password' => Hash::make('saefudinkolektor'),
        ]);

        User::create([
            'name' => 'Arti (Kolektor Lapangan)',
            'email' => 'arti@kolektor.net',
            'phone' => '081234567103',
            'role' => 'collector',
            'is_active' => true,
            'password' => Hash::make('artikolektor'),
        ]);

        User::create([
            'name' => 'Aji (Kolektor Lapangan)',
            'email' => 'aji@kolektor.net',
            'phone' => '081234567104',
            'role' => 'collector',
            'is_active' => true,
            'password' => Hash::make('ajikolektor'),
        ]);

        User::create([
            'name' => 'Bagas (Kolektor Lapangan)',
            'email' => 'bagas@kolektor.net',
            'phone' => '081234567105',
            'role' => 'collector',
            'is_active' => true,
            'password' => Hash::make('bagaskolektor'),
        ]);
    }

    public function test_deleted_primary_technician_and_collector_cannot_login(): void
    {
        $responseTeknisiEmail = $this->post('/login', [
            'email' => 'teknisi@banterpol.net',
            'password' => 'teknisi',
        ]);
        $this->assertGuest();
        $responseTeknisiEmail->assertSessionHasErrors('email');

        $responseTeknisiUser = $this->post('/login', [
            'email' => 'teknisi',
            'password' => 'teknisi',
        ]);
        $this->assertGuest();
        $responseTeknisiUser->assertSessionHasErrors('email');

        $responseKolektorEmail = $this->post('/login', [
            'email' => 'kolektor@banterpol.net',
            'password' => 'kolektor',
        ]);
        $this->assertGuest();
        $responseKolektorEmail->assertSessionHasErrors('email');

        $responseKolektorUser = $this->post('/login', [
            'email' => 'kolektor',
            'password' => 'kolektor',
        ]);
        $this->assertGuest();
        $responseKolektorUser->assertSessionHasErrors('email');
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

    public function test_okta_can_login_with_email_and_username(): void
    {
        $responseEmail = $this->post('/login', [
            'email' => 'okta@teknisi.net',
            'password' => 'oktateknisi',
        ]);
        $this->assertAuthenticated();
        $responseEmail->assertRedirect(route('teknisi.dashboard'));

        auth()->logout();

        $responseUser = $this->post('/login', [
            'email' => 'okta',
            'password' => 'oktateknisi',
        ]);
        $this->assertAuthenticated();
        $responseUser->assertRedirect(route('teknisi.dashboard'));
    }

    public function test_dila_can_login_with_email_and_username(): void
    {
        $responseEmail = $this->post('/login', [
            'email' => 'dila@kolektor.net',
            'password' => 'dilakolektor',
        ]);
        $this->assertAuthenticated();
        $responseEmail->assertRedirect(route('kolektor.dashboard'));

        auth()->logout();

        $responseUser = $this->post('/login', [
            'email' => 'dila',
            'password' => 'dilakolektor',
        ]);
        $this->assertAuthenticated();
        $responseUser->assertRedirect(route('kolektor.dashboard'));
    }

    public function test_saefudin_can_login_with_email_and_username(): void
    {
        $responseEmail = $this->post('/login', [
            'email' => 'saefudin@kolektor.net',
            'password' => 'saefudinkolektor',
        ]);
        $this->assertAuthenticated();
        $responseEmail->assertRedirect(route('kolektor.dashboard'));

        auth()->logout();

        $responseUser = $this->post('/login', [
            'email' => 'saefudin',
            'password' => 'saefudinkolektor',
        ]);
        $this->assertAuthenticated();
        $responseUser->assertRedirect(route('kolektor.dashboard'));
    }

    public function test_arti_can_login_with_email_and_username(): void
    {
        $responseEmail = $this->post('/login', [
            'email' => 'arti@kolektor.net',
            'password' => 'artikolektor',
        ]);
        $this->assertAuthenticated();
        $responseEmail->assertRedirect(route('kolektor.dashboard'));

        auth()->logout();

        $responseUser = $this->post('/login', [
            'email' => 'arti',
            'password' => 'artikolektor',
        ]);
        $this->assertAuthenticated();
        $responseUser->assertRedirect(route('kolektor.dashboard'));
    }

    public function test_aji_kolektor_can_login_with_email_and_username(): void
    {
        $responseEmail = $this->post('/login', [
            'email' => 'aji@kolektor.net',
            'password' => 'ajikolektor',
        ]);
        $this->assertAuthenticated();
        $responseEmail->assertRedirect(route('kolektor.dashboard'));

        auth()->logout();

        $responseUser = $this->post('/login', [
            'email' => 'aji',
            'password' => 'ajikolektor',
        ]);
        $this->assertAuthenticated();
        $responseUser->assertRedirect(route('kolektor.dashboard'));
    }

    public function test_bagas_can_login_with_email_and_username(): void
    {
        $responseEmail = $this->post('/login', [
            'email' => 'bagas@kolektor.net',
            'password' => 'bagaskolektor',
        ]);
        $this->assertAuthenticated();
        $responseEmail->assertRedirect(route('kolektor.dashboard'));

        auth()->logout();

        $responseUser = $this->post('/login', [
            'email' => 'bagas',
            'password' => 'bagaskolektor',
        ]);
        $this->assertAuthenticated();
        $responseUser->assertRedirect(route('kolektor.dashboard'));
    }

    public function test_technician_and_collector_cannot_login_with_wrong_password(): void
    {
        $responseMamat = $this->post('/login', [
            'email' => 'mamat@teknisi.net',
            'password' => 'wrongpassword',
        ]);
        $this->assertGuest();
        $responseMamat->assertSessionHasErrors('email');

        $responseDila = $this->post('/login', [
            'email' => 'dila@kolektor.net',
            'password' => 'wrongpassword',
        ]);
        $this->assertGuest();
        $responseDila->assertSessionHasErrors('email');
    }
}
