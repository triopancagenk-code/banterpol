<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class EmptyStateViewsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_customer_views_empty_laporan_tagihan_pemesanan(): void
    {
        $customer = User::factory()->create([
            'name' => 'Pelanggan Uji',
            'role' => 'customer',
        ]);

        // 1. Tagihan Kosong
        $tagihanRes = $this->actingAs($customer)->get('/tagihan');
        $tagihanRes->assertStatus(200);
        $tagihanRes->assertSee('Semua Tagihan Lunas');
        $tagihanRes->assertSee('Tidak Ada Tunggakan');
        $tagihanRes->assertSee('Tidak ada data riwayat tagihan untuk filter yang dipilih.');
        $tagihanRes->assertSee('Tagihan Dibuat');
        $tagihanRes->assertSee('Jatuh Tempo');
        $tagihanRes->assertSee('Pengingat');
        $tagihanRes->assertSee('Keamanan Terjamin');

        // 2. Laporan Masalah Kosong
        $laporanRes = $this->actingAs($customer)->get('/laporan-masalah');
        $laporanRes->assertStatus(200);
        $laporanRes->assertSee('Belum ada laporan kendala aktif');

        // 3. Detail Pemesanan Kosong
        $orderRes = $this->actingAs($customer)->get('/order/detail');
        $orderRes->assertStatus(200);
        $orderRes->assertSee('Belum Ada Pemesanan Aktif');
        $orderRes->assertSee('Pilih Paket Langganan');
    }

    public function test_admin_views_empty_laporan_tagihan_pemesanan(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        // 1. Dashboard Admin Kosong
        $dashRes = $this->actingAs($admin)->get('/admin/dashboard');
        $dashRes->assertStatus(200);
        $dashRes->assertSee('Belum ada pesanan masuk.');
        $dashRes->assertSee('Belum ada data tagihan.');

        // 2. Pesanan Admin Kosong
        $pesananRes = $this->actingAs($admin)->get('/admin/pesanan');
        $pesananRes->assertStatus(200);
        $pesananRes->assertSee('Tidak ada pesanan yang ditemukan');
        $pesananRes->assertSee('Belum ada antrean pemesanan baru dari pelanggan saat ini.');

        // 3. Tagihan Admin Kosong
        $tagihanRes = $this->actingAs($admin)->get('/admin/tagihan');
        $tagihanRes->assertStatus(200);
        $tagihanRes->assertSee('Tidak ada tagihan yang cocok dengan filter atau pencarian.');
    }

    public function test_collector_views_empty_laporan_tagihan_pemesanan(): void
    {
        $collector = User::factory()->create([
            'role' => 'collector',
        ]);

        // 1. Dashboard Kolektor Kosong
        $dashRes = $this->actingAs($collector)->get('/kolektor/dashboard');
        $dashRes->assertStatus(200);
        $dashRes->assertSee('Semua tagihan prioritas sudah berhasil dilunasi!');
        $dashRes->assertSee('Tidak ada pesanan baru.');

        // 2. Tagihan Kolektor Kosong
        $tagihanRes = $this->actingAs($collector)->get('/kolektor/tagihan');
        $tagihanRes->assertStatus(200);
        $tagihanRes->assertSee('Tidak ada data tagihan');

        // 3. Pemasangan Kolektor Kosong
        $pemasanganRes = $this->actingAs($collector)->get('/kolektor/pemasangan');
        $pemasanganRes->assertStatus(200);
        $pemasanganRes->assertSee('Tidak ada tiket pemasangan');

        // 4. Gangguan Kolektor Kosong
        $gangguanRes = $this->actingAs($collector)->get('/kolektor/gangguan');
        $gangguanRes->assertStatus(200);
        $gangguanRes->assertSee('Tidak ada tiket gangguan');
    }

    public function test_technician_views_empty_laporan_dan_pemesanan(): void
    {
        $technician = User::factory()->create([
            'role' => 'technician',
        ]);

        // 1. Dashboard Teknisi Kosong
        $dashRes = $this->actingAs($technician)->get('/teknisi/dashboard');
        $dashRes->assertStatus(200);
        $dashRes->assertSee('Tidak ada antrean pemasangan tertunda saat ini.');

        // 2. Pemasangan Teknisi Kosong
        $pemasanganRes = $this->actingAs($technician)->get('/teknisi/pemasangan');
        $pemasanganRes->assertStatus(200);
        $pemasanganRes->assertSee('Tidak ada tiket pemasangan');

        // 3. Gangguan Teknisi Kosong
        $gangguanRes = $this->actingAs($technician)->get('/teknisi/gangguan');
        $gangguanRes->assertStatus(200);
        $gangguanRes->assertSee('Tidak ada tiket gangguan');
    }
}
