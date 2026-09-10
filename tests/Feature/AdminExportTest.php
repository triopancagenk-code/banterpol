<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminExportTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminUser(): User
    {
        return User::factory()->create([
            'role' => 'admin',
        ]);
    }

    private function createCustomerUser(): User
    {
        return User::factory()->create([
            'role' => 'customer',
        ]);
    }

    public function test_customer_views_show_berlangganan_label(): void
    {
        $customer = $this->createCustomerUser();

        // Cek halaman home / navigasi & footer
        $response = $this->actingAs($customer)->get('/');
        $response->assertOk();
        $response->assertSee('Berlangganan');

        // Cek halaman paket internet
        $response = $this->actingAs($customer)->get('/paket');
        $response->assertOk();
        $response->assertSee('WiFi Banterpool - Berlangganan Paket Internet', false);
        $response->assertSee('Pilih Paket Berlangganan Terbaik Untukmu', false);
    }

    public function test_guests_and_customers_cannot_access_admin_export(): void
    {
        // Tamu tanpa login
        $this->get('/admin/pesanan/export')->assertRedirect('/login');
        $this->get('/admin/tagihan/export')->assertRedirect('/login');

        // Pelanggan reguler
        $customer = $this->createCustomerUser();
        $this->actingAs($customer)->get('/admin/pesanan/export')->assertForbidden();
        $this->actingAs($customer)->get('/admin/tagihan/export')->assertForbidden();
    }

    public function test_admin_can_export_pesanan_excel(): void
    {
        $admin = $this->createAdminUser();

        $order = Order::create([
            'order_number' => 'ORD-20260907-999',
            'customer_name' => 'Bambang Sudirman',
            'customer_phone' => '081234567890',
            'customer_email' => 'bambang@test.com',
            'address' => 'Jl. Pahlawan No. 12, Cilongok',
            'package_name' => 'Paket 50 Mbps',
            'speed' => '50 Mbps',
            'price' => 300000,
            'installation_fee' => 50000,
            'tax' => 33000,
            'total' => 383000,
            'status' => 'Menunggu Konfirmasi',
            'payment_status' => 'Belum Bayar',
        ]);

        $response = $this->actingAs($admin)->get('/admin/pesanan/export');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8');
        
        $disposition = $response->headers->get('content-disposition');
        $this->assertStringContainsString('Rekap_Pesanan_Banterpool_', $disposition);
        $this->assertStringContainsString('.xls', $disposition);

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('LAPORAN REKAPITULASI PEMESANAN PELANGGAN BARU', $content);
        $this->assertStringContainsString('ORD-20260907-999', $content);
        $this->assertStringContainsString('Bambang Sudirman', $content);
    }

    public function test_admin_can_export_pesanan_with_filters(): void
    {
        $admin = $this->createAdminUser();

        Order::create([
            'order_number' => 'ORD-FILTER-MATCH',
            'customer_name' => 'Kucing Oren',
            'customer_phone' => '0811111111',
            'customer_email' => 'kucing.oren@test.com',
            'address' => 'Alamat Oren',
            'package_name' => 'Paket 20 Mbps',
            'price' => 200000,
            'total' => 200000,
            'status' => 'Sedang Dipasang',
        ]);

        Order::create([
            'order_number' => 'ORD-FILTER-OTHER',
            'customer_name' => 'Kucing Hitam',
            'customer_phone' => '0822222222',
            'customer_email' => 'kucing.hitam@test.com',
            'address' => 'Alamat Hitam',
            'package_name' => 'Paket 10 Mbps',
            'price' => 150000,
            'total' => 150000,
            'status' => 'Selesai',
        ]);

        $response = $this->actingAs($admin)->get('/admin/pesanan/export?status=Sedang+Dipasang');
        $response->assertOk();

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('ORD-FILTER-MATCH', $content);
        $this->assertStringNotContainsString('ORD-FILTER-OTHER', $content);
    }

    public function test_admin_can_export_tagihan_excel(): void
    {
        $admin = $this->createAdminUser();

        Bill::create([
            'bill_number' => 'INV-TEST-0099',
            'customer_name' => 'Sinta Dewi',
            'customer_phone' => '0899998888',
            'customer_email' => 'sinta@test.com',
            'address' => 'Perum Cilongok Indah No. 3',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'period' => '01 Sep 2026 – 01 Okt 2026',
            'due_date' => '05 Sep 2026',
            'bill_date' => '01 Sep 2026',
            'amount' => 200000,
            'tax' => 22000,
            'total' => 222000,
            'status' => 'Belum Bayar',
        ]);

        $response = $this->actingAs($admin)->get('/admin/tagihan/export');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8');

        $disposition = $response->headers->get('content-disposition');
        $this->assertStringContainsString('Rekap_Tagihan_Banterpool_', $disposition);
        $this->assertStringContainsString('.xls', $disposition);

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('LAPORAN REKAPITULASI MONITORING TAGIHAN & INVOICE PELANGGAN', $content);
        $this->assertStringContainsString('INV-TEST-0099', $content);
        $this->assertStringContainsString('Sinta Dewi', $content);
    }

    public function test_admin_pages_render_export_excel_buttons(): void
    {
        $admin = $this->createAdminUser();

        $responsePesanan = $this->actingAs($admin)->get('/admin/pesanan');
        $responsePesanan->assertOk();
        $responsePesanan->assertSee('Export Excel');
        $responsePesanan->assertSee(route('admin.pesanan.export'));

        $responseTagihan = $this->actingAs($admin)->get('/admin/tagihan');
        $responseTagihan->assertOk();
        $responseTagihan->assertSee('Export Excel');
        $responseTagihan->assertSee(route('admin.tagihan.export'));
    }

    public function test_admin_odc_map_uses_google_maps(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get('/admin/odc-map');
        $response->assertOk();
        $response->assertSee('maps.googleapis.com/maps/api/js', false);
        $response->assertSee('odcGisMap');
        $response->assertSee('google.maps.Map', false);
        $response->assertSee('google.maps.Marker', false);
        $response->assertSee('google.maps.Polyline', false);
        $response->assertDontSee('leaflet.js', false);
    }

    public function test_customer_booking_uses_google_maps(): void
    {
        $customer = $this->createCustomerUser();

        $response = $this->actingAs($customer)->get('/paket');
        $response->assertOk();
        $response->assertSee('maps.googleapis.com/maps/api/js', false);
        $response->assertSee('map-house-picker');
        $response->assertSee('google.maps.Map', false);
        $response->assertSee('google.maps.Marker', false);
        $response->assertDontSee('leaflet.js', false);
    }
}
