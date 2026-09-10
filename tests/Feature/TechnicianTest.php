<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TechnicianTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_technician_dashboard(): void
    {
        $response = $this->get('/teknisi/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_customers_cannot_access_technician_dashboard(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $response = $this->actingAs($customer)->get('/teknisi/dashboard');
        $response->assertForbidden();
    }

    public function test_technician_login_redirects_directly_to_technician_dashboard(): void
    {
        $technician = User::factory()->create([
            'email' => 'teknisi.lapangan@banterpool.net',
            'password' => Hash::make('password123'),
            'role' => 'technician',
        ]);

        // Simulasikan jika sebelumnya ada intended URL di session
        $response = $this->withSession(['url.intended' => '/'])
            ->post('/login', [
                'email' => 'teknisi.lapangan@banterpool.net',
                'password' => 'password123',
            ]);

        $response->assertRedirect(route('teknisi.dashboard'));
    }

    public function test_technician_is_redirected_to_technician_dashboard_when_accessing_home(): void
    {
        $technician = User::factory()->create([
            'role' => 'technician',
        ]);

        $response = $this->actingAs($technician)->get('/');
        $response->assertRedirect(route('teknisi.dashboard'));
    }

    public function test_technician_is_redirected_to_gangguan_when_accessing_laporan_masalah(): void
    {
        $technician = User::factory()->create([
            'role' => 'technician',
        ]);

        $response = $this->actingAs($technician)->get('/laporan-masalah');
        $response->assertRedirect(route('teknisi.gangguan'));
    }

    public function test_technician_can_view_dashboard(): void
    {
        $technician = User::factory()->create([
            'role' => 'technician',
        ]);

        $response = $this->actingAs($technician)->get('/teknisi/dashboard');
        $response->assertStatus(200);
        $response->assertSee('TEKNISI');
        $response->assertSee('Dashboard');
    }

    public function test_technician_can_view_pemasangan_tickets(): void
    {
        $technician = User::factory()->create([
            'role' => 'technician',
        ]);

        $response = $this->actingAs($technician)->get('/teknisi/pemasangan');
        $response->assertStatus(200);
        $response->assertSee('Tiket Pemasangan WiFi Pelanggan Baru');
    }

    public function test_technician_can_view_gangguan_tickets(): void
    {
        $technician = User::factory()->create([
            'role' => 'technician',
        ]);

        $response = $this->actingAs($technician)->get('/teknisi/gangguan');
        $response->assertStatus(200);
        $response->assertSee('Tiket Gangguan Jaringan');
    }

    public function test_technician_can_update_order_status(): void
    {
        $technician = User::factory()->create([
            'name' => 'Teknisi Test',
            'role' => 'technician',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-TEST-001',
            'customer_name' => 'Pelanggan Uji',
            'customer_phone' => '081234567890',
            'customer_email' => 'uji@example.com',
            'address' => 'Jl. Uji Coba No. 1, Banyumas',
            'package_name' => 'Paket 20 Mbps',
            'price' => 200000,
            'total' => 222000,
            'status' => 'Jadwal Teknisi',
        ]);

        $response = $this->actingAs($technician)->post("/teknisi/pemasangan/{$order->id}/status", [
            'status' => 'Selesai',
            'assigned_odp' => 'ODP-CLK-99 (Port 01)',
            'ont_sn' => 'ZTEG99887766',
            'opm_dbm' => '-18.5 dBm',
            'technician_notes' => 'Pemasangan sukses, sinyal prima.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'Selesai',
            'assigned_odp' => 'ODP-CLK-99 (Port 01)',
            'ont_sn' => 'ZTEG99887766',
            'opm_dbm' => '-18.5 dBm',
        ]);
    }
}
