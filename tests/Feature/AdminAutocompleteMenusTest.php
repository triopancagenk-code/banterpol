<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Material;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAutocompleteMenusTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin.auto@banterpool.net',
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_pesanan_autocomplete(): void
    {
        Order::create([
            'order_number' => 'ORD-112233',
            'customer_name' => 'Fajar Pratama',
            'customer_email' => 'fajar@example.com',
            'customer_phone' => '081234567111',
            'address' => 'Jl. Kenanga No. 12',
            'package_name' => 'Paket Gamer 50 Mbps',
            'speed' => '50 Mbps',
            'price' => 250000,
            'total' => 250000,
            'status' => 'Menunggu Konfirmasi',
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('admin.pesanan', [
            'ajax' => 1,
            'q' => 'Fajar',
        ]));

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertNotEmpty($data);
        $this->assertEquals('Fajar Pratama', $data[0]['customer_name']);
        $this->assertEquals('ORD-112233', $data[0]['order_number']);

        // Check view contains autocomplete elements
        $viewRes = $this->actingAs($this->admin)->get(route('admin.pesanan'));
        $viewRes->assertStatus(200);
        $viewRes->assertSee('x-data="{', false);
        $viewRes->assertSee('@input.debounce.150ms="onInput()"', false);
    }

    public function test_tagihan_and_rekap_autocomplete(): void
    {
        // Tagihan aktif (monitoring tagihan)
        Bill::create([
            'bill_number' => 'INV-202611-ACT01',
            'customer_name' => 'Gita Gutawa',
            'customer_email' => 'gita@example.com',
            'customer_phone' => '081234567222',
            'address' => 'Jl. Melati No. 5',
            'package_name' => 'Paket Rumah 20 Mbps',
            'speed' => '20 Mbps',
            'period' => '01 Nov 2026 – 30 Nov 2026',
            'due_date' => '20 Nov 2026',
            'amount' => 150000,
            'total' => 150000,
            'status' => 'Belum Bayar',
        ]);

        // Tagihan lunas (rekap pembayaran)
        Bill::create([
            'bill_number' => 'INV-202611-REC01',
            'customer_name' => 'Hendra Setiawan',
            'customer_email' => 'hendra@example.com',
            'customer_phone' => '081234567333',
            'address' => 'Jl. Mawar No. 8',
            'package_name' => 'Paket Bisnis 50 Mbps',
            'speed' => '50 Mbps',
            'period' => '01 Nov 2026 – 30 Nov 2026',
            'due_date' => '20 Nov 2026',
            'amount' => 300000,
            'total' => 300000,
            'status' => 'Lunas',
        ]);

        // Autocomplete on monitoring tagihan
        $resTagihan = $this->actingAs($this->admin)->getJson(route('admin.tagihan', [
            'ajax' => 1,
            'status' => 'all',
            'q' => 'Gita',
        ]));
        $resTagihan->assertStatus(200);
        $dataTagihan = $resTagihan->json();
        $this->assertNotEmpty($dataTagihan);
        $this->assertEquals('Gita Gutawa', $dataTagihan[0]['customer_name']);

        // Autocomplete on rekap pembayaran
        $resRekap = $this->actingAs($this->admin)->getJson(route('admin.tagihan', [
            'ajax' => 1,
            'status' => 'rekap',
            'q' => 'Hendra',
        ]));
        $resRekap->assertStatus(200);
        $dataRekap = $resRekap->json();
        $this->assertNotEmpty($dataRekap);
        $this->assertEquals('Hendra Setiawan', $dataRekap[0]['customer_name']);

        // Check view contains autocomplete elements
        $viewRes = $this->actingAs($this->admin)->get(route('admin.tagihan'));
        $viewRes->assertStatus(200);
        $viewRes->assertSee('searchVal:', false);
        $viewRes->assertSee('@input.debounce.150ms="onInput()"', false);
    }

    public function test_stok_material_autocomplete(): void
    {
        Material::create([
            'code' => 'KBL-FBR-001',
            'name' => 'Kabel Dropcore Fiber 1 Core 1000m',
            'category' => 'Kabel',
            'unit' => 'Roll',
            'stock' => 15,
            'min_stock' => 5,
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('admin.material.index', [
            'ajax' => 1,
            'q' => 'Kabel Dropcore',
        ]));

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertNotEmpty($data);
        $this->assertEquals('Kabel Dropcore Fiber 1 Core 1000m', $data[0]['name']);
        $this->assertEquals('KBL-FBR-001', $data[0]['code']);
    }

    public function test_laporan_masalah_autocomplete(): void
    {
        $response = $this->actingAs($this->admin)->getJson(route('admin.laporan', [
            'autocomplete' => 1,
            'q' => 'Nanang',
        ]));

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertIsArray($data);

        // Check view has autocomplete elements
        $viewRes = $this->actingAs($this->admin)->get(route('admin.laporan'));
        $viewRes->assertStatus(200);
        $viewRes->assertSee('searchVal:', false);
        $viewRes->assertSee('@input.debounce.150ms="onInput()"', false);
    }
}
