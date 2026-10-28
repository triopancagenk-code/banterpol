<?php

namespace Tests\Feature;

use App\Models\DailyReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_technician_can_access_daily_report_portal(): void
    {
        $technician = User::factory()->create([
            'role' => 'technician',
            'name' => 'Budi Setiawan',
        ]);

        $response = $this->actingAs($technician)->get('/daily-report');

        $response->assertStatus(200);
        $response->assertSee('ISP Field');
        $response->assertSee('Technician');
        $response->assertSee('Portal');
        $response->assertSee('Total Pekerjaan Hari Ini');
        $response->assertSee('Kas Terkumpul (Teknisi)');
        $response->assertSee('Est. Biaya Material (Hari Ini)');
        $response->assertSee('Input Laporan & Pembukuan', false);
        $response->assertSee('Informasi Pelanggan & Kredensial', false);
        $response->assertSee('Parameter Teknis & Harga Material', false);
        $response->assertSee('Katalog Material & Harga Satuan', false);
        $response->assertSee('Pembukuan / Transaksi Lapangan', false);
        $response->assertSee('Simpan & Kirim ke Spreadsheet', false);

        // Verify the 13 partner options exist
        $response->assertSee('ThreeFive');
        $response->assertSee('INKA');
        $response->assertSee('BANTERPOL');
        $response->assertSee('TRIPANCA');
        $response->assertSee('DANA ID');
        $response->assertSee('OBORNET');
        $response->assertSee('JANE HOME');
        $response->assertSee('KIDANG MAS');
        $response->assertSee('BUMDES PERNASIDI');
        $response->assertSee('TX ID');
        $response->assertSee('BUMDES CIPETE');
        $response->assertSee('MAHSUS');
        $response->assertSee('BERKAH JAYA');
    }

    public function test_technician_can_store_daily_report_with_materials_and_cash(): void
    {
        $technician = User::factory()->create([
            'role' => 'technician',
            'name' => 'Budi Setiawan',
        ]);

        $postData = [
            'report_date' => now()->toDateString(),
            'technician_name' => 'Budi Setiawan',
            'partner_company' => 'BANTERPOL',
            'job_type' => 'Pasang Baru (PSB / Installation)',
            'customer_name' => 'Bpk Herman / CST-109',
            'customer_phone' => '081234567890',
            'customer_address' => 'Jl. Merdeka No. 45, Purwokerto',
            'pppoe_username' => 'herman_net',
            'odp_port' => 'ODP-TMB-04 / Port 2',
            'rx_power' => '-19.5',
            'ont_sn' => 'ZTEGC819A2B',
            'materials_json' => json_encode([
                ['name' => 'Kabel Dropcore', 'price' => 2500, 'qty' => 85, 'unit' => 'm', 'total' => 212500],
                ['name' => 'Fast Connector', 'price' => 7500, 'qty' => 2, 'unit' => 'pcs', 'total' => 15000],
                ['name' => 'Roset FO', 'price' => 12000, 'qty' => 1, 'unit' => 'pcs', 'total' => 12000],
            ]),
            'material_total_cost' => 239500,
            'transaction_type' => 'Biaya Pasang Baru (PSB)',
            'cash_amount' => 250000,
            'payment_method' => 'Tunai (Cash)',
            'cash_deposit_status' => 'Belum Disetor',
        ];

        $response = $this->actingAs($technician)->post('/daily-report', $postData);

        $response->assertRedirect('/daily-report');
        $this->assertDatabaseHas('daily_reports', [
            'customer_name' => 'Bpk Herman / CST-109',
            'pppoe_username' => 'herman_net',
            'partner_company' => 'BANTERPOL',
            'cash_amount' => 250000,
            'material_total_cost' => 239500,
            'cash_deposit_status' => 'Belum Disetor',
        ]);
    }

    public function test_technician_can_setor_kas(): void
    {
        $technician = User::factory()->create([
            'role' => 'technician',
            'name' => 'Budi Setiawan',
        ]);

        $report = DailyReport::create([
            'report_date' => now()->toDateString(),
            'technician_name' => 'Budi Setiawan',
            'partner_company' => 'BANTERPOL',
            'job_type' => 'Pasang Baru (PSB / Installation)',
            'customer_name' => 'Bpk Herman / CST-109',
            'transaction_type' => 'Biaya Pasang Baru (PSB)',
            'cash_amount' => 250000,
            'payment_method' => 'Tunai (Cash)',
            'cash_deposit_status' => 'Belum Disetor',
        ]);

        $response = $this->actingAs($technician)->post("/daily-report/{$report->id}/setor");

        $response->assertRedirect();
        $this->assertDatabaseHas('daily_reports', [
            'id' => $report->id,
            'cash_deposit_status' => 'Sudah Disetor',
        ]);
    }

    public function test_technician_can_export_daily_report_csv(): void
    {
        $technician = User::factory()->create([
            'role' => 'technician',
        ]);

        $response = $this->actingAs($technician)->get('/daily-report/export');

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'text/csv'));
    }

    public function test_admindaily_can_login_and_redirects_to_daily_report_portal(): void
    {
        $user = User::factory()->create([
            'name' => 'Budi Setiawan',
            'email' => 'admindaily',
            'role' => 'technician',
            'password' => \Illuminate\Support\Facades\Hash::make('admindaily'),
        ]);

        $response = $this->post('/login', [
            'email' => 'admindaily',
            'password' => 'admindaily',
        ]);

        $response->assertRedirect(route('daily-report.index'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_admindaily_can_access_daily_report_portal(): void
    {
        $user = User::factory()->create([
            'name' => 'Budi Setiawan',
            'email' => 'admindaily',
            'role' => 'technician',
            'password' => \Illuminate\Support\Facades\Hash::make('admindaily'),
        ]);

        $response = $this->actingAs($user)->get('/daily-report');

        $response->assertStatus(200);
        $response->assertSee('ISP Field');
        $response->assertSee('Technician');
        $response->assertSee('Portal');
    }

    public function test_teknisi_daily_report_alias_redirects(): void
    {
        $technician = User::factory()->create([
            'role' => 'technician',
        ]);

        $response = $this->actingAs($technician)->get('/teknisi/daily-report');

        $response->assertRedirect(route('daily-report.index'));
    }

    public function test_daily_report_catalog_follows_incoming_materials_of_current_month(): void
    {
        $technician = User::factory()->create([
            'role' => 'technician',
            'name' => 'Budi Setiawan',
        ]);

        $mat1 = \App\Models\Material::create([
            'code' => 'TEST-RTR',
            'name' => 'Router ZTE F609',
            'category' => 'Router & AP Wireless',
            'unit' => 'Unit',
            'unit_price' => 0,
            'stock' => 10,
        ]);

        $mat2 = \App\Models\Material::create([
            'code' => 'TEST-ADP',
            'name' => 'Adaptor 12v',
            'category' => 'Power & Adaptor',
            'unit' => 'Unit',
            'unit_price' => 0,
            'stock' => 10,
        ]);

        \App\Models\MaterialMutation::create([
            'material_id' => $mat1->id,
            'user_id' => $technician->id,
            'type' => 'in',
            'amount' => 4,
            'transaction_date' => now()->toDateString(),
            'notes' => 'Barang Masuk Uji Coba',
        ]);

        \App\Models\MaterialMutation::create([
            'material_id' => $mat2->id,
            'user_id' => $technician->id,
            'type' => 'in',
            'amount' => 4,
            'transaction_date' => now()->toDateString(),
            'notes' => 'Barang Masuk Uji Coba',
        ]);

        $response = $this->actingAs($technician)->get('/daily-report');

        $response->assertStatus(200);
        $response->assertSee('Router ZTE F609');
        $response->assertSee('Adaptor 12v');
        $response->assertSee('Barang Masuk:');
    }

    public function test_manual_material_added_from_daily_report_is_registered_to_admin_incoming_stock_for_current_month(): void
    {
        $technician = User::factory()->create([
            'role' => 'technician',
            'name' => 'Budi Setiawan',
        ]);

        $postData = [
            'name' => 'Kabel LAN Cat6 50M',
            'category' => 'Kabel LAN & UTP',
            'unit' => 'Roll',
            'price' => 150000,
            'qty' => 5,
        ];

        $response = $this->actingAs($technician)->postJson('/daily-report/tambah-barang-masuk', $postData);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'material' => [
                'name' => 'Kabel LAN Cat6 50M',
                'category' => 'Kabel LAN & UTP',
                'unit' => 'Roll',
                'price' => 150000,
                'qty' => 5,
            ]
        ]);

        // Pastikan material dibuat di tabel materials
        $this->assertDatabaseHas('materials', [
            'name' => 'Kabel LAN Cat6 50M',
            'category' => 'Kabel LAN & UTP',
            'unit' => 'Roll',
        ]);

        $material = \App\Models\Material::where('name', 'Kabel LAN Cat6 50M')->first();

        // Pastikan tercatat sebagai mutasi barang masuk (type = 'in') bulan ini
        $mutation = \App\Models\MaterialMutation::where('material_id', $material->id)->first();
        $this->assertNotNull($mutation);
        $this->assertEquals('in', $mutation->type);
        $this->assertEquals(5, (float)$mutation->amount);
        $this->assertEquals(now()->toDateString(), $mutation->transaction_date->toDateString());

        // Verifikasi admin dapat melihat barang masuk ini di dashboard stok admin tab masuk
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $adminResponse = $this->actingAs($admin)->get('/admin/stok-material?tab=masuk');
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('Kabel LAN Cat6 50M');
    }

    public function test_daily_report_material_qty_cannot_exceed_incoming_stock_of_current_month(): void
    {
        $technician = User::factory()->create([
            'role' => 'technician',
            'name' => 'Budi Setiawan',
        ]);

        $adaptor = \App\Models\Material::create([
            'code' => 'TEST-ADP-12V',
            'name' => 'Adaptor 12v',
            'category' => 'Power & Adaptor',
            'unit' => 'Unit',
            'unit_price' => 25000,
            'stock' => 7,
        ]);

        // Simulasikan 2 transaksi barang masuk untuk Adaptor 12v di bulan berjalan (3 + 4 = 7 unit)
        \App\Models\MaterialMutation::create([
            'material_id' => $adaptor->id,
            'user_id' => $technician->id,
            'type' => 'in',
            'amount' => 3,
            'transaction_date' => now()->toDateString(),
            'notes' => 'Pak Mono',
        ]);
        \App\Models\MaterialMutation::create([
            'material_id' => $adaptor->id,
            'user_id' => $technician->id,
            'type' => 'in',
            'amount' => 4,
            'transaction_date' => now()->toDateString(),
            'notes' => 'Aris',
        ]);

        // 1. Cek di halaman daily-report: Adaptor 12v memiliki batas maksimal 7
        $response = $this->actingAs($technician)->get('/daily-report');
        $response->assertStatus(200);
        $response->assertSee('Adaptor 12v');

        $defaultCatalog = $response->viewData('defaultCatalog');
        $this->assertNotEmpty($defaultCatalog);
        $adaptorInCatalog = collect($defaultCatalog)->firstWhere('name', 'Adaptor 12v');
        $this->assertNotNull($adaptorInCatalog);
        $this->assertEquals(7, $adaptorInCatalog['max_qty']);

        // 2. Jika teknisi submit laporan dengan qty 12 (melebihi stok masuk 7)
        $postData = [
            'report_date' => now()->toDateString(),
            'technician_name' => 'Budi Setiawan',
            'partner_company' => 'BANTERPOL',
            'job_type' => 'Pasang Baru (PSB / Installation)',
            'customer_name' => 'Bpk Herman / CST-109',
            'materials_json' => json_encode([
                ['name' => 'Adaptor 12v', 'price' => 25000, 'qty' => 12, 'unit' => 'Unit', 'total' => 300000],
            ]),
            'material_total_cost' => 175000,
            'transaction_type' => 'Biaya Pasang Baru (PSB)',
            'cash_amount' => 250000,
            'payment_method' => 'Tunai (Cash)',
            'cash_deposit_status' => 'Belum Disetor',
        ];

        $storeResponse = $this->actingAs($technician)->post('/daily-report', $postData);
        $storeResponse->assertRedirect('/daily-report');

        // Laporan tersimpan dengan qty yang dibatasi maksimal 7 unit
        $report = \App\Models\DailyReport::latest('id')->first();
        $this->assertNotNull($report);
        $savedMaterials = $report->materials_json;
        $this->assertEquals(7, (float)$savedMaterials[0]['qty']);
    }
}
