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

    public function test_technician_pemasangan_view_contains_material_dropdown_options(): void
    {
        $technician = User::factory()->create([
            'role' => 'technician',
            'name' => 'Teknisi Anto',
        ]);

        $response = $this->actingAs($technician)->get('/teknisi/pemasangan');
        $response->assertStatus(200);
        $response->assertSee('Penggunaan Material');
        $response->assertSee('Kabel Drop &amp; Fiber Optik', false);
        $response->assertSee('Modem &amp; ONT GPON', false);
        $response->assertSee('Protection Sleeve (Smuft)');
        $response->assertSee('Power &amp; Adaptor', false);
        $response->assertSee('Clamp &amp; Aksesoris', false);
    }

    public function test_technician_can_update_order_with_material_used(): void
    {
        $technician = User::factory()->create([
            'role' => 'technician',
            'name' => 'Teknisi Anto',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-MAT-TEST-001',
            'customer_name' => 'Budi Material Test',
            'customer_phone' => '081234567890',
            'customer_email' => 'budi.mat@example.com',
            'address' => 'Jl. Material Test No. 9',
            'package_name' => 'Paket 20 Mbps',
            'price' => 200000,
            'total' => 222000,
            'status' => 'Sedang Dipasang',
        ]);

        $response = $this->actingAs($technician)->post("/teknisi/pemasangan/{$order->id}/status", [
            'status' => 'Selesai',
            'assigned_odp' => 'ODP-MAT-01 (Port 02)',
            'ont_sn' => 'ZTEG11223344',
            'opm_dbm' => '-19.2 dBm',
            'material_used' => 'Drop core per meter (85 Meter), Modem (1 Unit), Adaptor (1 Unit), Smurf (2 Pcs)',
            'technician_notes' => 'Pemasangan rapi dan sinyal prima.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'Selesai',
            'material_used' => 'Drop core per meter (85 Meter), Modem (1 Unit), Adaptor (1 Unit), Smurf (2 Pcs)',
        ]);
    }

    public function test_technician_material_usage_creates_outgoing_mutation_visible_in_admin_pov(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Gudang',
        ]);

        $technician = User::factory()->create([
            'role' => 'technician',
            'name' => 'Teknisi Slamet',
        ]);

        // Siapkan master barang
        $dropCore = \App\Models\Material::updateOrCreate(['code' => 'A044'], [
            'name' => 'DC 1C',
            'category' => 'Kabel Drop & Fiber Optik',
            'unit' => 'Meter',
            'stock' => 500,
            'initial_stock' => 500,
            'total_out' => 0,
        ]);

        $modem = \App\Models\Material::updateOrCreate(['code' => 'A096'], [
            'name' => 'Modem Zte F670L',
            'category' => 'Modem & ONT GPON',
            'unit' => 'Unit',
            'stock' => 20,
            'initial_stock' => 20,
            'total_out' => 0,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-SYNC-777',
            'customer_name' => 'Pelanggan Fiber VIP',
            'customer_phone' => '089876543210',
            'customer_email' => 'fiber.vip@example.com',
            'address' => 'Komplek Permata Hijau Blok C1',
            'package_name' => 'Paket 50 Mbps',
            'price' => 350000,
            'total' => 388500,
            'status' => 'Sedang Dipasang',
            'technician' => $technician->name,
            'technician_id' => $technician->id,
        ]);

        // 1. Teknisi mengisi material di portal teknisi
        $response = $this->actingAs($technician)->post("/teknisi/pemasangan/{$order->id}/status", [
            'status' => 'Selesai',
            'material_used' => 'Drop core per meter (75 Meter), Modem (1 Unit)',
            'material_items' => json_encode([
                ['type' => 'Drop core per meter', 'qty' => '75 Meter'],
                ['type' => 'Modem', 'qty' => '1 Unit'],
            ]),
        ]);

        $response->assertRedirect();

        // 2. Cek stok berkurang
        $dropCore->refresh();
        $modem->refresh();
        $this->assertEquals(425, (float)$dropCore->stock);
        $this->assertEquals(75, (float)$dropCore->total_out);
        $this->assertEquals(19, (float)$modem->stock);
        $this->assertEquals(1, (float)$modem->total_out);

        // 3. Cek mutasi barang keluar dibuat dengan order_id
        $this->assertDatabaseHas('material_mutations', [
            'order_id' => $order->id,
            'material_id' => $dropCore->id,
            'type' => 'out',
            'amount' => 75,
        ]);
        $this->assertDatabaseHas('material_mutations', [
            'order_id' => $order->id,
            'material_id' => $modem->id,
            'type' => 'out',
            'amount' => 1,
        ]);

        // 4. Admin melihat data Barang Keluar di tab keluar
        $adminResponse = $this->actingAs($admin)->get(route('admin.material.index', ['tab' => 'keluar']));
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('ORD-SYNC-777');
        $adminResponse->assertSee('Pelanggan Fiber VIP');
        $adminResponse->assertSee('Teknisi Slamet');

        // 5. Jika teknisi mengedit penggunaan material (misal revisi kabel jadi 50m saja), stok lama di-rollback
        $this->actingAs($technician)->post("/teknisi/pemasangan/{$order->id}/status", [
            'status' => 'Selesai',
            'material_used' => 'Drop core per meter (50 Meter)',
            'material_items' => json_encode([
                ['type' => 'Drop core per meter', 'qty' => '50 Meter'],
            ]),
        ]);

        $dropCore->refresh();
        $modem->refresh();
        // Drop core: 500 - 50 = 450
        $this->assertEquals(450, (float)$dropCore->stock);
        // Modem dikembalikan stoknya karena tidak dipakai lagi: 19 + 1 = 20
        $this->assertEquals(20, (float)$modem->stock);
    }
}


