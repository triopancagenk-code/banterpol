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

    public function test_admin_can_bulk_delete_selected_tickets(): void
    {
        $admin = $this->createAdminUser();

        // Admin selects only TCK-202605-001 and TCK-202605-003, leaving TCK-202605-002
        $response = $this->actingAs($admin)
            ->withHeaders(['Accept' => 'application/json'])
            ->postJson('/admin/laporan-masalah/bulk-delete', [
                'ids' => ['TCK-202605-001', 'TCK-202605-003'],
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'deleted_count' => 2,
        ]);

        $cachedTickets = Cache::get('trouble_tickets');
        $this->assertCount(2, $cachedTickets);
        $remainingIds = collect($cachedTickets)->pluck('id')->all();
        $this->assertContains('TCK-202605-002', $remainingIds);
        $this->assertContains('TCK-202605-004', $remainingIds);
        $this->assertNotContains('TCK-202605-001', $remainingIds);
        $this->assertNotContains('TCK-202605-003', $remainingIds);
    }

    public function test_admin_can_bulk_delete_via_standard_form_redirect(): void
    {
        $admin = $this->createAdminUser();

        // Submit via standard HTML form post with redirect
        $response = $this->actingAs($admin)->post('/admin/laporan-masalah/bulk-delete', [
            'ids' => ['TCK-202605-001'],
        ]);

        $response->assertRedirect('/admin/laporan-masalah');
        $response->assertSessionHas('success');

        $cachedTickets = Cache::get('trouble_tickets');
        $found = collect($cachedTickets)->firstWhere('id', 'TCK-202605-001');
        $this->assertNull($found);
    }

    public function test_admin_can_bulk_delete_via_ids_json(): void
    {
        $admin = $this->createAdminUser();

        // Submit via form with ids_json
        $response = $this->actingAs($admin)->post('/admin/laporan-masalah/bulk-delete', [
            'ids_json' => json_encode(['TCK-202605-002']),
        ]);

        $response->assertRedirect('/admin/laporan-masalah');
        $response->assertSessionHas('success');

        $cachedTickets = Cache::get('trouble_tickets');
        $found = collect($cachedTickets)->firstWhere('id', 'TCK-202605-002');
        $this->assertNull($found);
    }

    private function createDirekturUser(): User
    {
        return User::factory()->create([
            'name' => 'Direktur Utama',
            'email' => 'direktur.noc@banterpool.net',
            'role' => 'direktur',
        ]);
    }

    public function test_auto_prune_deletes_tickets_older_than_3_days_for_admin_and_direktur_pov(): void
    {
        $admin = $this->createAdminUser();
        $direktur = $this->createDirekturUser();
        $now = now('Asia/Jakarta')->locale('id');

        // Setup tiket:
        // 1. Tiket baru 1 hari lalu (Sedang Ditangani) -> TIDAK terhapus
        // 2. Tiket lama 4 hari lalu tapi belum selesai (Menunggu Respon) -> TIDAK terhapus (karena belum Selesai)
        // 3. Tiket lama 4 hari lalu sudah selesai (Selesai) -> OTOMATIS terhapus
        $recentDate = $now->copy()->subDays(1);
        $expiredDate = $now->copy()->subDays(4);

        $initialTickets = [
            [
                'id' => 'TCK-RECENT-001',
                'customer_name' => 'Pelanggan Baru',
                'customer_phone' => '081234567891',
                'address' => 'Jl. Baru No. 1',
                'odp' => 'ODP-CLK-01',
                'type' => 'LOS Merah',
                'category' => 'LOS',
                'priority' => 'Kritis',
                'description' => 'Kendala 1 hari lalu.',
                'status' => 'Sedang Ditangani',
                'technician' => 'Randi',
                'created_at' => $recentDate->translatedFormat('l, d F Y, H:i') . ' WIB',
                'created_date' => $recentDate->translatedFormat('l, d F Y'),
                'created_time' => $recentDate->format('H:i:s') . ' WIB',
                'created_at_iso' => $recentDate->toIso8601String(),
                'created_timestamp' => $recentDate->timestamp,
                'updated_at' => $recentDate->translatedFormat('l, d F Y, H:i') . ' WIB',
                'updated_date' => $recentDate->translatedFormat('l, d F Y'),
                'updated_time' => $recentDate->format('H:i:s') . ' WIB',
                'updated_at_iso' => $recentDate->toIso8601String(),
                'updated_timestamp' => $recentDate->timestamp,
            ],
            [
                'id' => 'TCK-UNRESOLVED-002',
                'customer_name' => 'Pelanggan Belum Selesai',
                'customer_phone' => '081234567893',
                'address' => 'Jl. Belum Selesai No. 3',
                'odp' => 'ODP-CLK-03',
                'type' => 'WiFi Putus',
                'category' => 'WiFi',
                'priority' => 'Tinggi',
                'description' => 'Kendala 4 hari lalu tapi belum beres penanganannya.',
                'status' => 'Menunggu Respon',
                'technician' => 'Belum Ditugaskan',
                'created_at' => $expiredDate->translatedFormat('l, d F Y, H:i') . ' WIB',
                'created_date' => $expiredDate->translatedFormat('l, d F Y'),
                'created_time' => $expiredDate->format('H:i:s') . ' WIB',
                'created_at_iso' => $expiredDate->toIso8601String(),
                'created_timestamp' => $expiredDate->timestamp,
                'updated_at' => $expiredDate->translatedFormat('l, d F Y, H:i') . ' WIB',
                'updated_date' => $expiredDate->translatedFormat('l, d F Y'),
                'updated_time' => $expiredDate->format('H:i:s') . ' WIB',
                'updated_at_iso' => $expiredDate->toIso8601String(),
                'updated_timestamp' => $expiredDate->timestamp,
            ],
            [
                'id' => 'TCK-EXPIRED-003',
                'customer_name' => 'Pelanggan Lama Selesai',
                'customer_phone' => '081234567892',
                'address' => 'Jl. Lama No. 9',
                'odp' => 'ODP-CLK-02',
                'type' => 'Koneksi Lambat',
                'category' => 'Drop Speed',
                'priority' => 'Normal',
                'description' => 'Kendala 4 hari lalu sudah lampau dan beres.',
                'status' => 'Selesai',
                'technician' => 'Bambang',
                'created_at' => $expiredDate->translatedFormat('l, d F Y, H:i') . ' WIB',
                'created_date' => $expiredDate->translatedFormat('l, d F Y'),
                'created_time' => $expiredDate->format('H:i:s') . ' WIB',
                'created_at_iso' => $expiredDate->toIso8601String(),
                'created_timestamp' => $expiredDate->timestamp,
                'updated_at' => $expiredDate->translatedFormat('l, d F Y, H:i') . ' WIB',
                'updated_date' => $expiredDate->translatedFormat('l, d F Y'),
                'updated_time' => $expiredDate->format('H:i:s') . ' WIB',
                'updated_at_iso' => $expiredDate->toIso8601String(),
                'updated_timestamp' => $expiredDate->timestamp,
            ],
        ];

        Cache::forever('trouble_tickets', $initialTickets);
        session(['admin_tickets' => $initialTickets]);

        // 1. Verifikasi POV Admin: Tiket 4 hari lalu yang SUDAH SELESAI otomatis hilang.
        // Tiket yang belum selesai (TCK-UNRESOLVED-002) dan tiket baru (TCK-RECENT-001) TETAP ADA.
        $adminResponse = $this->actingAs($admin)->get('/admin/laporan-masalah');
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('Auto-Hapus 3 Hari');
        $adminResponse->assertSee('TCK-RECENT-001');
        $adminResponse->assertSee('TCK-UNRESOLVED-002');
        $adminResponse->assertDontSee('TCK-EXPIRED-003');

        // Pastikan di Cache: TCK-EXPIRED-003 terhapus, sisa 2 tiket
        $cachedAfterAdmin = Cache::get('trouble_tickets');
        $this->assertCount(2, $cachedAfterAdmin);
        $remainingIds = collect($cachedAfterAdmin)->pluck('id')->all();
        $this->assertContains('TCK-RECENT-001', $remainingIds);
        $this->assertContains('TCK-UNRESOLVED-002', $remainingIds);
        $this->assertNotContains('TCK-EXPIRED-003', $remainingIds);

        // 2. Verifikasi POV Direktur: Akses halaman yang sama, data sinkron
        $direkturResponse = $this->actingAs($direktur)->get('/admin/laporan-masalah');
        $direkturResponse->assertStatus(200);
        $direkturResponse->assertSee('Auto-Hapus 3 Hari');
        $direkturResponse->assertSee('TCK-RECENT-001');
        $direkturResponse->assertSee('TCK-UNRESOLVED-002');
        $direkturResponse->assertDontSee('TCK-EXPIRED-003');

        // 3. Verifikasi JSON Polling POV Direktur membawa info auto_prune 3 hari
        $jsonResponse = $this->actingAs($direktur)
            ->withHeaders(['Accept' => 'application/json'])
            ->get('/admin/laporan-masalah');
        $jsonResponse->assertStatus(200);
        $jsonResponse->assertJson([
            'success' => true,
            'auto_prune' => [
                'active' => true,
                'retention_days' => 3,
            ],
        ]);
        $tickets = $jsonResponse->json('tickets');
        $this->assertCount(2, $tickets);
    }

    public function test_admin_and_direktur_can_manually_delete_selected_and_all_tickets_regardless_of_status(): void
    {
        $admin = $this->createAdminUser();
        $direktur = $this->createDirekturUser();

        // 1. Siapkan tiket dengan berbagai status (Menunggu Respon, Sedang Ditangani, Selesai)
        $sampleTickets = [
            ['id' => 'MAN-01', 'customer_name' => 'User 1', 'status' => 'Menunggu Respon'],
            ['id' => 'MAN-02', 'customer_name' => 'User 2', 'status' => 'Sedang Ditangani'],
            ['id' => 'MAN-03', 'customer_name' => 'User 3', 'status' => 'Selesai'],
        ];

        Cache::forever('trouble_tickets', $sampleTickets);

        // 2. Direktur dapat menghapus tiket pilihan secara manual walaupun statusnya belum selesai
        $responseDirektur = $this->actingAs($direktur)
            ->withHeaders(['Accept' => 'application/json'])
            ->postJson('/admin/laporan-masalah/bulk-delete', [
                'ids' => ['MAN-01'], // Menghapus tiket Menunggu Respon secara manual
            ]);

        $responseDirektur->assertStatus(200);
        $responseDirektur->assertJson([
            'success' => true,
            'deleted_count' => 1,
        ]);

        $cached = Cache::get('trouble_tickets');
        $this->assertCount(2, $cached);
        $this->assertNull(collect($cached)->firstWhere('id', 'MAN-01'));
        $this->assertNotNull(collect($cached)->firstWhere('id', 'MAN-02'));
        $this->assertNotNull(collect($cached)->firstWhere('id', 'MAN-03'));

        // 3. Admin dapat menghapus seluruh data secara manual
        $responseAdmin = $this->actingAs($admin)
            ->withHeaders(['Accept' => 'application/json'])
            ->postJson('/admin/laporan-masalah/bulk-delete', [
                'delete_all' => 1,
            ]);

        $responseAdmin->assertStatus(200);
        $responseAdmin->assertJson([
            'success' => true,
            'deleted_count' => 2,
        ]);

        $cachedFinal = Cache::get('trouble_tickets');
        $this->assertEmpty($cachedFinal);
    }

    public function test_artisan_tickets_prune_command_deletes_tickets_older_than_3_days(): void
    {
        $now = now('Asia/Jakarta');
        $expiredDate = $now->copy()->subDays(5);
        $recentDate = $now->copy()->subDays(1);

        $tickets = [
            [
                'id' => 'TCK-CLI-EXP-01',
                'customer_name' => 'User CLI Expired',
                'created_at_iso' => $expiredDate->toIso8601String(),
                'created_timestamp' => $expiredDate->timestamp,
            ],
            [
                'id' => 'TCK-CLI-REC-02',
                'customer_name' => 'User CLI Recent',
                'created_at_iso' => $recentDate->toIso8601String(),
                'created_timestamp' => $recentDate->timestamp,
            ],
        ];

        Cache::forever('trouble_tickets', $tickets);

        $this->artisan('tickets:prune', ['--days' => 3])
            ->assertExitCode(0)
            ->expectsOutputToContain('Auto-hapus riwayat laporan masalah selesai.')
            ->expectsOutputToContain('Tiket dihapus : 1 tiket');

        $remaining = Cache::get('trouble_tickets');
        $this->assertCount(1, $remaining);
        $this->assertEquals('TCK-CLI-REC-02', $remaining[0]['id']);
    }

    public function test_auto_prune_removes_physical_attachment_file_for_expired_tickets(): void
    {
        $now = now('Asia/Jakarta');
        $expiredDate = $now->copy()->subDays(5);

        // Siapkan file fisik dummy
        $testDir = public_path('uploads/laporan');
        if (!file_exists($testDir)) {
            mkdir($testDir, 0755, true);
        }
        $dummyFileName = 'test_expired_ticket_' . time() . '.jpg';
        $dummyPath = $testDir . DIRECTORY_SEPARATOR . $dummyFileName;
        file_put_contents($dummyPath, 'fake-image-content');
        $this->assertFileExists($dummyPath);

        $tickets = [
            [
                'id' => 'TCK-ATTACH-EXP-01',
                'customer_name' => 'User With Attachment',
                'created_at_iso' => $expiredDate->toIso8601String(),
                'created_timestamp' => $expiredDate->timestamp,
                'attachment_url' => '/uploads/laporan/' . $dummyFileName,
            ],
        ];

        Cache::forever('trouble_tickets', $tickets);

        // Jalankan prune dengan retensi 3 hari
        \App\Http\Controllers\Admin\AdminController::pruneOldTickets(null, 3);

        // Verifikasi file fisik terhapus
        $this->assertFileDoesNotExist($dummyPath);
    }
}

