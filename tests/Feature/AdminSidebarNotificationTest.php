<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AdminSidebarNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);
    }

    public function test_sidebar_shows_no_notifications_when_no_data_exists(): void
    {
        Cache::forget('trouble_tickets');

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $response->assertOk();

        // Tidak ada pesanan, tidak ada pelanggan, tidak ada tagihan, tidak ada tiket
        $this->assertEquals(0, Order::where('order_number', 'not like', 'PLG-%')->count());
        $this->assertEquals(0, Order::forCustomerData()->count());
        $this->assertEquals(0, Bill::activeForMonitoring()->count());

        $content = $response->getContent();

        // Pastikan link Monitoring Pesanan TIDAK memiliki badge angka
        preg_match('/href="[^"]*admin\/pesanan"[^>]*>[\s\S]*?<\/a>/i', $content, $mPesanan);
        $this->assertNotEmpty($mPesanan);
        $this->assertStringNotContainsString('rounded-full', $mPesanan[0]);

        // Pastikan link Data Pelanggan TIDAK memiliki badge angka
        preg_match('/href="[^"]*admin\/pelanggan"[^>]*>[\s\S]*?<\/a>/i', $content, $mPelanggan);
        $this->assertNotEmpty($mPelanggan);
        $this->assertStringNotContainsString('rounded-full', $mPelanggan[0]);

        // Pastikan link Monitoring Tagihan TIDAK memiliki badge angka
        preg_match('/href="[^"]*admin\/tagihan"[^>]*>[\s\S]*?<\/a>/i', $content, $mTagihan);
        $this->assertNotEmpty($mTagihan);
        $this->assertStringNotContainsString('rounded-full', $mTagihan[0]);
    }

    public function test_sidebar_shows_exact_numeric_badges_when_data_exists(): void
    {
        // 1. Tambah data pesanan di Monitoring Pesanan
        $order = Order::create([
            'order_number' => 'ORD-20261001-111',
            'customer_name' => 'Pesanan Uji Coba',
            'customer_phone' => '081234567899',
            'customer_email' => 'pesanan111@example.com',
            'address' => 'Desa Karanganyar, Banyumas',
            'package_name' => 'Paket 30 Mbps',
            'speed' => '30 Mbps',
            'price' => 165000,
            'total' => 165000,
            'status' => 'Jadwal Teknisi',
        ]);

        // 2. Tambah pelanggan di Data Pelanggan
        Order::create([
            'order_number' => 'PLG-2026-0001',
            'customer_name' => 'Pelanggan Lama Terdaftar',
            'customer_phone' => '081234567888',
            'customer_email' => 'pelangganlama@example.com',
            'address' => 'Banyumas',
            'package_name' => 'Paket 20 Mbps',
            'status' => 'Selesai',
        ]);

        // 3. Tambah tagihan belum bayar (Monitoring Tagihan)
        Bill::create([
            'bill_number' => 'INV-202610-001',
            'customer_name' => 'Pelanggan Tagihan',
            'customer_phone' => '081234567877',
            'customer_email' => 'pelanggantagihan@example.com',
            'address' => 'Jl. Kenanga No. 10, Banyumas',
            'package_name' => 'Paket 20 Mbps',
            'period' => 'Okt 2026',
            'amount' => 110000,
            'tax' => 0,
            'total' => 110000,
            'status' => 'Belum Bayar',
            'due_date' => '05 Nov 2026',
            'bill_date' => '01 Okt 2026',
        ]);

        // 4. Tambah tagihan lunas (Rekap Pembayaran)
        Bill::create([
            'bill_number' => 'INV-202610-002',
            'customer_name' => 'Pelanggan Lunas',
            'customer_phone' => '081234567866',
            'customer_email' => 'pelangganlunas@example.com',
            'address' => 'Jl. Mawar No. 12, Banyumas',
            'package_name' => 'Paket 20 Mbps',
            'period' => 'Okt 2026',
            'amount' => 110000,
            'tax' => 0,
            'total' => 110000,
            'status' => 'Lunas',
            'due_date' => '05 Nov 2026',
            'bill_date' => '01 Okt 2026',
        ]);

        // 5. Tambah tiket kendala aktif (Laporan Masalah)
        Cache::forever('trouble_tickets', [
            [
                'id' => 'TCK-202610-001',
                'customer_name' => 'Pelanggan Komplain',
                'type' => 'LOS / Lampu Merah Kedip',
                'priority' => 'Kritis',
                'description' => 'Lampu indikator merah berkedip',
                'odp' => 'ODP-CLK-01',
                'status' => 'Menunggu Respon',
                'created_at' => now()->toIso8601String(),
            ]
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $response->assertOk();

        $content = $response->getContent();

        // Monitoring Pesanan: Ada 1 order
        preg_match('/href="[^"]*admin\/pesanan"[^>]*>[\s\S]*?<\/a>/i', $content, $mPesanan);
        $this->assertNotEmpty($mPesanan);
        $this->assertStringContainsString('1', $mPesanan[0]);

        // Data Pelanggan: Ada 1 pelanggan
        preg_match('/href="[^"]*admin\/pelanggan"[^>]*>[\s\S]*?<\/a>/i', $content, $mPelanggan);
        $this->assertNotEmpty($mPelanggan);
        $this->assertStringContainsString('1', $mPelanggan[0]);

        // Monitoring Tagihan: Ada 1 tagihan
        preg_match('/href="[^"]*admin\/tagihan"[^>]*>[\s\S]*?<\/a>/i', $content, $mTagihan);
        $this->assertNotEmpty($mTagihan);
        $this->assertStringContainsString('1', $mTagihan[0]);

        // Rekap Pembayaran: Ada 1 tagihan lunas
        preg_match('/href="[^"]*admin\/tagihan\?status=rekap"[^>]*>[\s\S]*?<\/a>/i', $content, $mRekap);
        $this->assertNotEmpty($mRekap);
        $this->assertStringContainsString('1', $mRekap[0]);

        // Laporan Masalah: Ada 1 tiket aktif
        preg_match('/href="[^"]*admin\/laporan-masalah"[^>]*>[\s\S]*?<\/a>/i', $content, $mLaporan);
        $this->assertNotEmpty($mLaporan);
        $this->assertStringContainsString('1', $mLaporan[0]);
    }
}
