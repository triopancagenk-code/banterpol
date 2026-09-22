<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AdminLaporanMasalahTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function createAdminUser(): User
    {
        return User::factory()->create([
            'role' => 'admin',
        ]);
    }

    private function createCustomerUser(): User
    {
        return User::factory()->create([
            'name' => 'Bambang Sudirman',
            'phone' => '081299887766',
            'role' => 'customer',
        ]);
    }

    public function test_admin_can_view_laporan_masalah_page(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get('/admin/laporan-masalah');

        $response->assertStatus(200);
        $response->assertSee('Daftar Tiket Gangguan Jaringan');
        $response->assertSee('Pengintaian & Penanganan Laporan Masalah');
        $response->assertSee('TCK-202605-001');
        $response->assertSee('Waktu Update Status (Real-Time)', false);
    }

    public function test_customer_report_appears_in_admin_pov_with_real_time(): void
    {
        $customer = $this->createCustomerUser();

        // Pelanggan membuat laporan kendala
        $response = $this->actingAs($customer)->post('/laporan-masalah', [
            'category' => 'Lampu Indikator LOS Merah',
            'sub_category' => 'LOS Merah Berkedip',
            'description' => 'Lampu LOS berkedip merah sejak pagi, router ZTE tidak dapat internet.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('ticket_success');

        $ticketSuccess = session('ticket_success');
        $newTicketId = $ticketSuccess['id'];
        $this->assertNotEmpty($newTicketId);

        // Admin membuka halaman laporan masalah
        $admin = $this->createAdminUser();
        $adminResponse = $this->actingAs($admin)->get('/admin/laporan-masalah');

        $adminResponse->assertStatus(200);
        // Pastikan laporan dari pelanggan muncul di POV admin
        $adminResponse->assertSee($newTicketId);
        $adminResponse->assertSee('Bambang Sudirman');
        $adminResponse->assertSee('Lampu Indikator LOS Merah');
    }

    public function test_admin_laporan_masalah_json_polling(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)
            ->withHeaders(['Accept' => 'application/json'])
            ->get('/admin/laporan-masalah');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $data = $response->json();
        $this->assertArrayHasKey('tickets', $data);
        $this->assertArrayHasKey('counts', $data);
        $this->assertArrayHasKey('system_time', $data);
        $this->assertStringContainsString('WIB', $data['system_time']);
    }

    public function test_admin_can_update_ticket_status_via_json_request_in_real_time(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)
            ->withHeaders(['Accept' => 'application/json'])
            ->postJson('/admin/laporan-masalah/TCK-202605-001/status', [
                'status' => 'Selesai',
                'technician' => 'Bambang Irawan (Perangkat)',
                'notes' => 'Kabel drop core telah diganti dan redaman optik kembali -18.5 dBm.',
                'client_time' => '09:55:12 WIB',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $data = $response->json();
        $this->assertNotEmpty($data['updated_at']);
        $this->assertStringContainsString('WIB', $data['updated_at']);
        $this->assertEquals('Selesai', $data['ticket']['status']);
        $this->assertEquals('Bambang Irawan (Perangkat)', $data['ticket']['technician']);
        $this->assertNotEmpty($data['ticket']['status_history']);
        $this->assertEquals('Selesai', $data['ticket']['status_history'][0]['status']);

        // Pastikan tiket di Cache / session tersimpan
        $cachedTickets = Cache::get('trouble_tickets');
        $this->assertNotNull($cachedTickets);
        $tck1 = collect($cachedTickets)->firstWhere('id', 'TCK-202605-001');
        $this->assertEquals('Selesai', $tck1['status']);
        $this->assertNotEmpty($tck1['updated_at']);
    }

    public function test_admin_can_update_ticket_status_via_form_post(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)
            ->post('/admin/laporan-masalah/TCK-202605-002/status', [
                'status' => 'Sedang Ditangani',
                'technician' => 'Fajar & Tim Lapangan',
                'notes' => 'Sedang menuju lokasi ODP-CLK-04.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $cachedTickets = Cache::get('trouble_tickets');
        $tck2 = collect($cachedTickets)->firstWhere('id', 'TCK-202605-002');
        $this->assertEquals('Sedang Ditangani', $tck2['status']);
        $this->assertEquals('Fajar & Tim Lapangan', $tck2['technician']);
        $this->assertNotEmpty($tck2['updated_at']);
        $this->assertStringContainsString('WIB', $tck2['updated_at']);
    }

    public function test_admin_pov_displays_real_time_indonesian_date_format(): void
    {
        $admin = $this->createAdminUser();
        $now = now('Asia/Jakarta')->locale('id');
        $expectedDate = $now->translatedFormat('l, d F Y');

        // 1. Cek view HTML admin laporan masalah
        $response = $this->actingAs($admin)->get('/admin/laporan-masalah');
        $response->assertStatus(200);
        $response->assertSee('Waktu Real-Time:');

        // 2. Cek JSON polling admin mengembalikan system_date dan system_time bahasa Indonesia
        $jsonResponse = $this->actingAs($admin)
            ->withHeaders(['Accept' => 'application/json'])
            ->get('/admin/laporan-masalah');

        $jsonResponse->assertStatus(200);
        $data = $jsonResponse->json();
        $this->assertEquals($expectedDate, $data['system_date']);
        $this->assertStringContainsString($expectedDate, $data['system_time']);

        // 3. Cek tiket pertama memiliki tanggal hari ini
        $firstTicket = $data['tickets'][0];
        $this->assertNotEmpty($firstTicket['created_date']);
        $this->assertNotEmpty($firstTicket['updated_date']);
        $this->assertStringContainsString('WIB', $firstTicket['created_time']);
        $this->assertStringContainsString('WIB', $firstTicket['updated_time']);
    }

    public function test_customer_uploaded_photo_appears_in_admin_pov_monitoring(): void
    {
        $customer = $this->createCustomerUser();
        $admin = $this->createAdminUser();

        // 1. Pelanggan upload foto bukti kendala
        $fakeImage = UploadedFile::fake()->image('bukti_lampu_los_merah.jpg', 800, 600);

        $response = $this->actingAs($customer)->post('/laporan-masalah', [
            'category' => 'Lampu Indikator LOS Merah',
            'sub_category' => 'LOS Merah Berkedip',
            'description' => 'Foto modem terlampir, lampu LOS merah kedip terus sejak pagi.',
            'attachment' => $fakeImage,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('ticket_success');

        // 2. Periksa tiket di cache trouble_tickets
        $cachedTickets = Cache::get('trouble_tickets');
        $this->assertNotNull($cachedTickets);
        $this->assertNotEmpty($cachedTickets);

        $latestTicket = $cachedTickets[0];
        $this->assertEquals('bukti_lampu_los_merah.jpg', $latestTicket['attachment']);
        $this->assertNotNull($latestTicket['attachment_url']);
        $this->assertStringContainsString('/uploads/laporan/', $latestTicket['attachment_url']);
        $this->assertEquals('image', $latestTicket['attachment_type']);

        // Pastikan file fisik tersimpan
        $filePath = public_path(ltrim($latestTicket['attachment_url'], '/'));
        $this->assertFileExists($filePath);

        // 3. Akses POV Admin: pastikan foto bukti muncul di view admin dan API polling
        $adminResponse = $this->actingAs($admin)->get('/admin/laporan-masalah');
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('Foto Bukti');
        $adminResponse->assertSee($latestTicket['id']);

        // Polling JSON juga membawa attachment_url
        $jsonResponse = $this->actingAs($admin)
            ->withHeaders(['Accept' => 'application/json'])
            ->get('/admin/laporan-masalah');
        $jsonResponse->assertStatus(200);

        $jsonTickets = $jsonResponse->json('tickets');
        $found = collect($jsonTickets)->firstWhere('id', $latestTicket['id']);
        $this->assertNotNull($found);
        $this->assertEquals($latestTicket['attachment_url'], $found['attachment_url']);
        $this->assertEquals('image', $found['attachment_type']);

        // Clean up test file
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    public function test_admin_laporan_masalah_page_has_neat_layout_matching_pelanggan_feature(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get('/admin/laporan-masalah');

        $response->assertStatus(200);

        // 1. KPI Statistic Cards
        $response->assertSee('Total Tiket');
        $response->assertSee('Menunggu Respon');
        $response->assertSee('Sedang Ditangani');
        $response->assertSee('Selesai Normal');
        $response->assertSee('Gangguan Kritis');

        // 2. Search & Filter Bar (4-Kolom)
        $response->assertSee('Pencarian Laporan Masalah');
        $response->assertSee('Status Tiket');
        $response->assertSee('Tingkat Prioritas');
        $response->assertSee('Kategori Kendala');

        // 3. Tabel Master & Komponen Tampilan Rapi
        $response->assertSee('Nama Pelanggan');
        $response->assertSee('No Handphone');
        $response->assertSee('Jenis Kendala & Bukti', false);
        $response->assertSee('Titik ODP');
        $response->assertSee('Teknisi Bertugas');
        $response->assertSee('wa.me');
    }

    public function test_admin_can_delete_single_ticket(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)
            ->withHeaders(['Accept' => 'application/json'])
            ->deleteJson('/admin/laporan-masalah/TCK-202605-001');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $cachedTickets = Cache::get('trouble_tickets');
        $found = collect($cachedTickets)->firstWhere('id', 'TCK-202605-001');
        $this->assertNull($found);
    }

    public function test_admin_can_bulk_delete_all_tickets(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)
            ->withHeaders(['Accept' => 'application/json'])
            ->postJson('/admin/laporan-masalah/bulk-delete', [
                'delete_all' => 1,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $cachedTickets = Cache::get('trouble_tickets');
        $this->assertEmpty($cachedTickets);
    }
}
