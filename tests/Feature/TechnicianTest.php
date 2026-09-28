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

    public function test_admin_can_assign_technician_and_ticket_appears_on_assigned_technician_account(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $techMamat = User::factory()->create([
            'name' => 'Mamat (Teknisi Lapangan)',
            'email' => 'mamat@teknisi.net',
            'role' => 'technician',
        ]);

        $techDanu = User::factory()->create([
            'name' => 'Danu (Teknisi Lapangan)',
            'email' => 'danu@teknisi.net',
            'role' => 'technician',
        ]);

        $order = Order::create([
            'order_number' => 'BTR-202609-0099',
            'customer_name' => 'Rafi Razani Test',
            'customer_phone' => '081234567891',
            'customer_email' => 'rafi@test.com',
            'address' => 'Kasegeran, Banyumas',
            'package_name' => 'Paket 50 Mbps',
            'price' => 220000,
            'total' => 220000,
            'status' => 'Menunggu Konfirmasi',
            'payment_status' => 'Lunas',
            'technician' => null,
        ]);

        // 1. Admin kelola pesanan dan menugaskan teknisi Mamat
        $response = $this->actingAs($admin)->post("/admin/pesanan/{$order->id}/status", [
            'status' => 'Jadwal Teknisi',
            'technician' => $techMamat->name,
            'assigned_odp' => 'ODP-CLK-01',
            'payment_status' => 'Lunas',
            'package_name' => 'Paket 50 Mbps',
        ]);

        $response->assertRedirect();
        $order->refresh();

        $this->assertEquals($techMamat->name, $order->technician);
        $this->assertEquals($techMamat->id, $order->technician_id);
        $this->assertEquals('Jadwal Teknisi', $order->status);
        $this->assertNotNull($order->assigned_at);

        // 2. Akun teknisi Mamat melihat dashboard & tiket pemasangan
        $mamatDashResponse = $this->actingAs($techMamat)->get('/teknisi/dashboard');
        $mamatDashResponse->assertStatus(200);
        $mamatDashResponse->assertSee('Rafi Razani Test');
        $mamatDashResponse->assertSee('BTR-202609-0099');
        $mamatDashResponse->assertSee('Ditugaskan ke Anda');

        $mamatTicketsResponse = $this->actingAs($techMamat)->get('/teknisi/pemasangan?scope=my');
        $mamatTicketsResponse->assertStatus(200);
        $mamatTicketsResponse->assertSee('Rafi Razani Test');
        $mamatTicketsResponse->assertSee('Tugas Anda');

        // 3. Akun teknisi Danu TIDAK memiliki tiket tersebut di 'scope=my'
        $danuTicketsResponse = $this->actingAs($techDanu)->get('/teknisi/pemasangan?scope=my');
        $danuTicketsResponse->assertStatus(200);
        $danuTicketsResponse->assertDontSee('Rafi Razani Test');
    }
}
