<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CollectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_collector_dashboard(): void
    {
        $response = $this->get('/kolektor/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_customers_cannot_access_collector_dashboard(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $response = $this->actingAs($customer)->get('/kolektor/dashboard');
        $response->assertForbidden();
    }

    public function test_technicians_cannot_access_collector_dashboard(): void
    {
        $technician = User::factory()->create([
            'role' => 'technician',
        ]);

        $response = $this->actingAs($technician)->get('/kolektor/dashboard');
        $response->assertForbidden();
    }

    public function test_collector_login_redirects_directly_to_collector_dashboard(): void
    {
        $collector = User::factory()->create([
            'email' => 'kolektor.test@banterpool.net',
            'password' => Hash::make('password123'),
            'role' => 'collector',
        ]);

        $response = $this->withSession(['url.intended' => '/'])
            ->post('/login', [
                'email' => 'kolektor.test@banterpool.net',
                'password' => 'password123',
            ]);

        $response->assertRedirect(route('kolektor.dashboard'));
    }

    public function test_collector_is_redirected_to_collector_dashboard_when_accessing_home(): void
    {
        $collector = User::factory()->create([
            'role' => 'collector',
        ]);

        $response = $this->actingAs($collector)->get('/');
        $response->assertRedirect(route('kolektor.dashboard'));
    }

    public function test_collector_is_redirected_to_collector_tagihan_when_accessing_customer_tagihan(): void
    {
        $collector = User::factory()->create([
            'role' => 'collector',
        ]);

        $response = $this->actingAs($collector)->get('/tagihan');
        $response->assertRedirect(route('kolektor.tagihan'));
    }

    public function test_collector_is_redirected_to_collector_gangguan_when_accessing_laporan_masalah(): void
    {
        $collector = User::factory()->create([
            'role' => 'collector',
        ]);

        $response = $this->actingAs($collector)->get('/laporan-masalah');
        $response->assertRedirect(route('kolektor.gangguan'));
    }

    public function test_collector_can_view_dashboard(): void
    {
        $collector = User::factory()->create([
            'name' => 'Kolektor Utama',
            'role' => 'collector',
        ]);

        $response = $this->actingAs($collector)->get('/kolektor/dashboard');
        $response->assertStatus(200);
        $response->assertSee('KOLEKTOR');
        $response->assertSee('Dashboard');
    }

    public function test_collector_can_view_tagihan(): void
    {
        $collector = User::factory()->create([
            'role' => 'collector',
        ]);

        $response = $this->actingAs($collector)->get('/kolektor/tagihan');
        $response->assertStatus(200);
        $response->assertSee('Manajemen Tagihan & Penerimaan Tunai', false);
    }

    public function test_collector_can_process_manual_cash_payment(): void
    {
        $collector = User::factory()->create([
            'name' => 'Bayu Kolektor',
            'role' => 'collector',
        ]);

        $bill = Bill::create([
            'bill_number' => 'INV-TEST-001',
            'customer_name' => 'Warga Cilongok',
            'customer_phone' => '081234567890',
            'address' => 'Desa Karanglo No. 10',
            'package_name' => 'Paket 20 Mbps',
            'period' => '01 Sep 2026 - 01 Okt 2026',
            'due_date' => '05 Sep 2026',
            'amount' => 110000,
            'tax' => 0,
            'total' => 110000,
            'status' => 'Belum Bayar',
        ]);

        $response = $this->actingAs($collector)->post('/kolektor/tagihan/bayar-tunai', [
            'bill_id' => $bill->id,
            'cash_amount' => 110000,
            'paid_date' => now()->format('Y-m-d'),
            'collector_notes' => 'Uang tunai diterima pas di ruang tamu pelanggan.',
        ]);

        $bill->refresh();

        $response->assertRedirect(route('kolektor.tagihan.kuitansi', $bill->id));
        $this->assertEquals('Lunas', $bill->status);
        $this->assertEquals('Tunai (Kolektor)', $bill->payment_method);
        $this->assertEquals('Bayu Kolektor', $bill->collected_by);
        $this->assertNotNull($bill->receipt_number);
    }

    public function test_collector_can_create_manual_bill(): void
    {
        $collector = User::factory()->create([
            'name' => 'Bayu Kolektor',
            'role' => 'collector',
        ]);

        $response = $this->actingAs($collector)->post('/kolektor/tagihan/input-manual', [
            'customer_name' => 'Pelanggan Manual Tunai',
            'customer_phone' => '089988776655',
            'address' => 'Jl. Pernasidi No. 99, Cilongok',
            'package_name' => 'Paket 50 Mbps',
            'speed' => '50 Mbps',
            'period' => '01 Sep 2026 - 01 Okt 2026',
            'due_date' => '05 Sep 2026',
            'total' => 220000,
            'is_paid_immediately' => 1,
            'collector_notes' => 'Langsung bayar tunai di lokasi.',
        ]);

        $this->assertDatabaseHas('bills', [
            'customer_name' => 'Pelanggan Manual Tunai',
            'total' => 220000,
            'status' => 'Lunas',
            'payment_method' => 'Tunai (Kolektor)',
        ]);
    }

    public function test_collector_can_view_receipt(): void
    {
        $collector = User::factory()->create([
            'name' => 'Bayu Kolektor',
            'role' => 'collector',
        ]);

        $bill = Bill::create([
            'bill_number' => 'INV-TEST-002',
            'receipt_number' => 'KWT-TEST-002',
            'customer_name' => 'Pelanggan Kuitansi',
            'customer_phone' => '081234567890',
            'address' => 'Desa Panembangan RT 01',
            'package_name' => 'Paket 20 Mbps',
            'period' => '01 Sep 2026 - 01 Okt 2026',
            'due_date' => '05 Sep 2026',
            'amount' => 110000,
            'tax' => 0,
            'total' => 110000,
            'status' => 'Lunas',
            'payment_method' => 'Tunai (Kolektor)',
            'paid_at' => now(),
            'collected_by' => 'Bayu Kolektor',
        ]);

        $response = $this->actingAs($collector)->get("/kolektor/tagihan/{$bill->id}/kuitansi");
        $response->assertStatus(200);
        $response->assertSee('KUITANSI PEMBAYARAN TUNAI');
        $response->assertSee('KWT-TEST-002');
        $response->assertSee('Pelanggan Kuitansi');
    }

    public function test_collector_can_view_pemasangan_tickets(): void
    {
        $collector = User::factory()->create([
            'role' => 'collector',
        ]);

        $response = $this->actingAs($collector)->get('/kolektor/pemasangan');
        $response->assertStatus(200);
        $response->assertSee('Monitoring Tiket Pemasangan Baru');
    }

    public function test_collector_can_view_gangguan_tickets(): void
    {
        $collector = User::factory()->create([
            'role' => 'collector',
        ]);

        $response = $this->actingAs($collector)->get('/kolektor/gangguan');
        $response->assertStatus(200);
        $response->assertSee('Monitoring Gangguan Jaringan Pelanggan');
    }
}
