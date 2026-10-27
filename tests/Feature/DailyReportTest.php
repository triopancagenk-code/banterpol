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
}
