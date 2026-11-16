<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Order;
use App\Models\User;
use App\Services\BillingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataPelangganDanMonitoringTagihanTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin NOC',
            'email' => 'admin.noc@banterpol.net',
        ]);
    }

    private function createCustomer(string $name, string $email, string $phone): User
    {
        return User::factory()->create([
            'role' => 'customer',
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
        ]);
    }

    /**
     * 1. Data pelanggan yang sudah ada (PLG- / pesanan lama) muncul di Monitoring Tagihan.
     */
    public function test_pelanggan_lama_muncul_di_monitoring_tagihan_dan_data_pelanggan(): void
    {
        $admin = $this->createAdmin();

        // Buat pelanggan yang sudah ada (kode PLG-)
        $existingOrder = Order::create([
            'order_number' => 'PLG-2026-0001',
            'customer_name' => 'Pak Joko Widodo',
            'customer_phone' => '081234560001',
            'customer_email' => 'joko@example.com',
            'address' => 'Jl. Lesmana No. 01, Banyumas',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'price' => 110000,
            'total' => 110000,
            'status' => 'Selesai',
            'payment_status' => 'Lunas',
            'created_at' => Carbon::parse('2026-01-01 08:00:00'),
        ]);

        // Pastikan pelanggan yang sudah ada muncul di Data Pelanggan
        $responsePelanggan = $this->actingAs($admin)->get(route('admin.pelanggan'));
        $responsePelanggan->assertOk();
        $responsePelanggan->assertSee('Pak Joko Widodo');
        $responsePelanggan->assertSee('PLG-2026-0001');

        // Buka Monitoring Tagihan
        $responseTagihan = $this->actingAs($admin)->get(route('admin.tagihan'));
        $responseTagihan->assertOk();

        // Tagihan bulanan untuk Pak Joko harus otomatis terbit dan muncul di Monitoring Tagihan
        $responseTagihan->assertSee('Pak Joko Widodo');
        $responseTagihan->assertSee('110.000');

        $bill = Bill::where('order_id', $existingOrder->id)->first();
        $this->assertNotNull($bill);
        $this->assertEquals('Pak Joko Widodo', $bill->customer_name);
        $this->assertEquals('Belum Bayar', $bill->status);
    }

    /**
     * 2. Pelanggan baru memesan hanya muncul di Data Pelanggan, dan tagihan bulan berjalannya
     *    TIDAK muncul di Monitoring Tagihan (Monitoring Tagihan mengikuti Data Pelanggan, bukan Monitoring Pesanan).
     */
    public function test_pelanggan_baru_memesan_hanya_muncul_di_data_pelanggan_dan_tidak_di_monitoring_tagihan_bulan_ini(): void
    {
        $admin = $this->createAdmin();
        $customerUser = $this->createCustomer('Rian Pelanggan Baru', 'rian@example.com', '081999888777');

        // Pelanggan baru memesan di bulan berjalan (status pesanan baru: Menunggu Konfirmasi / Selesai)
        $newOrder = Order::create([
            'order_number' => 'ORD-20261116-001',
            'user_id' => $customerUser->id,
            'customer_name' => $customerUser->name,
            'customer_phone' => $customerUser->phone,
            'customer_email' => $customerUser->email,
            'address' => 'Desa Kalisube, Banyumas',
            'package_name' => 'Paket 30 Mbps',
            'speed' => '30 Mbps',
            'price' => 165000,
            'total' => 165000,
            'status' => 'Menunggu Konfirmasi',
            'payment_status' => 'Menunggu Pembayaran',
            'created_at' => now('Asia/Jakarta'),
        ]);

        // 1. Muncul di Data Pelanggan
        $responsePelanggan = $this->actingAs($admin)->get(route('admin.pelanggan'));
        $responsePelanggan->assertOk();
        $responsePelanggan->assertSee('Rian Pelanggan Baru');
        $responsePelanggan->assertSee('ORD-20261116-001');

        // 2. Monitoring Tagihan admin TIDAK memunculkan tagihan untuk Rian di daftar tagihan aktif bulan berjalan
        $responseTagihan = $this->actingAs($admin)->get(route('admin.tagihan'));
        $responseTagihan->assertOk();
        $responseTagihan->assertViewHas('bills', function ($bills) use ($customerUser, $newOrder) {
            $orderIds = collect($bills)->pluck('order_id')->filter()->all();
            $customerNames = collect($bills)->pluck('customer_name')->all();
            return !in_array($newOrder->id, $orderIds) && !in_array($customerUser->name, $customerNames);
        });

        // Di database, tidak ada bill yang terbuat untuk Rian pada bulan berjalan
        $bill = Bill::where('order_id', $newOrder->id)->first();
        $this->assertNull($bill);
    }

    /**
     * 3. Tagihan untuk data pelanggan baru, baru muncul bulan depan.
     */
    public function test_tagihan_pelanggan_baru_baru_muncul_bulan_depan(): void
    {
        $admin = $this->createAdmin();
        $customerUser = $this->createCustomer('Siti Pelanggan Baru', 'siti@example.com', '081555666777');

        // Siti memesan dan dipasang pada bulan Oktober 2026
        $order = Order::create([
            'order_number' => 'ORD-20261020-002',
            'user_id' => $customerUser->id,
            'customer_name' => $customerUser->name,
            'customer_phone' => $customerUser->phone,
            'customer_email' => $customerUser->email,
            'address' => 'Desa Kedungwringin, Banyumas',
            'package_name' => 'Paket 30 Mbps',
            'speed' => '30 Mbps',
            'price' => 165000,
            'total' => 165000,
            'status' => 'Selesai',
            'payment_status' => 'Lunas',
            'installation_date' => '2026-10-20',
            'installed_at' => Carbon::parse('2026-10-20 14:00:00'),
            'created_at' => Carbon::parse('2026-10-20 10:00:00'),
        ]);

        // Pada bulan Oktober (bulan pemesanan), tagihan Siti belum muncul
        $october = Carbon::parse('2026-10-25 10:00:00', 'Asia/Jakarta');
        BillingService::syncMonthlyBillsFromDataPelanggan($october);
        $this->assertNull(Bill::where('order_id', $order->id)->first());

        // Pada bulan DEPAN (November 2026), saat tagihan terbit tgl 5:
        $november = Carbon::parse('2026-11-05 08:00:00', 'Asia/Jakarta');
        BillingService::syncMonthlyBillsFromDataPelanggan($november);

        // Tagihan Siti sekarang SUDAH MUNCUL untuk bulan November!
        $billNov = Bill::where('order_id', $order->id)->first();
        $this->assertNotNull($billNov);
        $this->assertEquals('Belum Bayar', $billNov->status);
        $this->assertStringContainsString('Nov 2026', $billNov->period);
        $this->assertStringContainsString('20 Nov 2026', $billNov->due_date);
        $this->assertEquals(165000, (float)$billNov->total);

        // Dan muncul di Monitoring Tagihan Admin
        $responseTagihan = $this->actingAs($admin)->get(route('admin.tagihan'));
        $responseTagihan->assertOk();
        $responseTagihan->assertSee('Siti Pelanggan Baru');
        $responseTagihan->assertSee($billNov->bill_number);
    }

    /**
     * 4. Dashboard NOC Admin memuat seluruh statistik KPI utama tanpa error (termasuk total_customers).
     */
    public function test_admin_dashboard_loads_with_all_kpi_stats(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertOk();
        $response->assertViewHas('stats', function ($stats) {
            return isset($stats['total_customers']) &&
                   isset($stats['active_customers']) &&
                   isset($stats['pending_orders_count']) &&
                   isset($stats['pending_bills_count']) &&
                   isset($stats['open_tickets_count']);
        });
        $response->assertSee('Pelanggan Aktif');
        $response->assertSee('Dashboard Operasional NOC');
    }
}
