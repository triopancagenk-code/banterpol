<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardSearchTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $direktur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin.test@banterpool.net',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->direktur = User::factory()->create([
            'email' => 'direktur@banterpool.net',
            'role' => 'direktur',
            'is_active' => true,
        ]);
    }

    public function test_admin_and_direktur_can_access_dashboard_page(): void
    {
        $responseAdmin = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertSee('Pencarian Cepat Operasional NOC');
        $responseAdmin->assertSee('Autocomplete Aktif');

        $responseDirektur = $this->actingAs($this->direktur)->get(route('admin.dashboard'));
        $responseDirektur->assertStatus(200);
        $responseDirektur->assertSee('Pencarian Cepat Operasional NOC');
    }

    public function test_autocomplete_returns_empty_json_for_blank_query(): void
    {
        $response = $this->actingAs($this->admin)->getJson(route('admin.dashboard', ['autocomplete' => 1, 'q' => '']));
        $response->assertStatus(200);
        $response->assertExactJson([]);
    }

    public function test_autocomplete_searches_customer_by_name_and_pppoe(): void
    {
        Order::create([
            'order_number' => 'PLG-2026-0099',
            'customer_name' => 'Budi Santoso Antigravity',
            'pppoe' => 'budisantoso99',
            'customer_email' => 'budi@example.com',
            'customer_phone' => '081234567890',
            'package_name' => 'Paket Ultra 50 Mbps',
            'speed' => '50 Mbps',
            'price' => 250000,
            'total' => 250000,
            'address' => 'Jl. Merdeka No. 10',
            'village' => 'Karangpucung',
            'status' => 'Selesai',
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('admin.dashboard', [
            'autocomplete' => 1,
            'q' => 'Budi',
        ]));

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertNotEmpty($data);
        $this->assertEquals('pelanggan', $data[0]['type']);
        $this->assertEquals('Budi Santoso Antigravity', $data[0]['title']);
        $this->assertStringContainsString('Karangpucung', $data[0]['subtitle']);

        // Test search by PPPoE
        $responsePppoe = $this->actingAs($this->direktur)->getJson(route('admin.dashboard', [
            'autocomplete' => 1,
            'q' => 'budisantoso99',
        ]));
        $responsePppoe->assertStatus(200);
        $dataPppoe = $responsePppoe->json();
        $this->assertNotEmpty($dataPppoe);
        $this->assertEquals('Budi Santoso Antigravity', $dataPppoe[0]['title']);
    }

    public function test_autocomplete_searches_bill_and_order(): void
    {
        // Pesanan baru
        Order::create([
            'order_number' => 'ORD-987654',
            'customer_name' => 'Citra Lestari',
            'customer_email' => 'citra@example.com',
            'customer_phone' => '08987654321',
            'address' => 'Jl. Citra No. 1',
            'package_name' => 'Paket Fiber 20 Mbps',
            'speed' => '20 Mbps',
            'price' => 175000,
            'total' => 175000,
            'status' => 'Menunggu Konfirmasi',
        ]);

        // Tagihan
        Bill::create([
            'bill_number' => 'INV-202611-999',
            'customer_name' => 'Dedi Firmansyah',
            'customer_email' => 'dedi@example.com',
            'customer_phone' => '08555544433',
            'address' => 'Jl. Dedi No. 2',
            'package_name' => 'Paket Rumah 20 Mbps',
            'speed' => '20 Mbps',
            'period' => '01 Nov 2026 – 30 Nov 2026',
            'due_date' => '20 Nov 2026',
            'amount' => 165000,
            'total' => 165000,
            'status' => 'Belum Bayar',
        ]);

        // Test order query
        $resOrder = $this->actingAs($this->admin)->getJson(route('admin.dashboard', [
            'autocomplete' => 1,
            'q' => 'ORD-987654',
        ]));
        $resOrder->assertStatus(200);
        $dataOrder = $resOrder->json();
        $this->assertNotEmpty($dataOrder);
        $this->assertEquals('pesanan', $dataOrder[0]['type']);
        $this->assertStringContainsString('ORD-987654', $dataOrder[0]['title']);

        // Test bill query
        $resBill = $this->actingAs($this->direktur)->getJson(route('admin.dashboard', [
            'autocomplete' => 1,
            'q' => 'INV-202611-999',
        ]));
        $resBill->assertStatus(200);
        $dataBill = $resBill->json();
        $this->assertNotEmpty($dataBill);
        $this->assertEquals('tagihan', $dataBill[0]['type']);
        $this->assertStringContainsString('INV-202611-999', $dataBill[0]['title']);
    }

    public function test_category_filter_in_autocomplete(): void
    {
        Order::create([
            'order_number' => 'PLG-2026-0005',
            'customer_name' => 'Eko Prasetyo',
            'customer_email' => 'eko@example.com',
            'customer_phone' => '08111222333',
            'address' => 'Jl. Eko No. 5',
            'package_name' => 'Paket 10 Mbps',
            'speed' => '10 Mbps',
            'price' => 120000,
            'total' => 120000,
            'status' => 'Selesai',
        ]);

        Bill::create([
            'bill_number' => 'INV-202611-005',
            'customer_name' => 'Eko Prasetyo',
            'customer_email' => 'eko@example.com',
            'customer_phone' => '08111222333',
            'address' => 'Jl. Eko No. 5',
            'package_name' => 'Paket 10 Mbps',
            'speed' => '10 Mbps',
            'period' => '01 Nov 2026 – 30 Nov 2026',
            'due_date' => '20 Nov 2026',
            'amount' => 120000,
            'total' => 120000,
            'status' => 'Belum Bayar',
        ]);

        // Filter type=pelanggan
        $resCustOnly = $this->actingAs($this->admin)->getJson(route('admin.dashboard', [
            'autocomplete' => 1,
            'q' => 'Eko',
            'type' => 'pelanggan',
        ]));
        $resCustOnly->assertStatus(200);
        $this->assertNotEmpty($resCustOnly->json());
        foreach ($resCustOnly->json() as $item) {
            $this->assertEquals('pelanggan', $item['type']);
        }

        // Filter type=tagihan
        $resBillOnly = $this->actingAs($this->admin)->getJson(route('admin.dashboard', [
            'autocomplete' => 1,
            'q' => 'Eko',
            'type' => 'tagihan',
        ]));
        $resBillOnly->assertStatus(200);
        $this->assertNotEmpty($resBillOnly->json());
        foreach ($resBillOnly->json() as $item) {
            $this->assertEquals('tagihan', $item['type']);
        }
    }

    public function test_dashboard_direct_form_submission_redirects(): void
    {
        // Direct submit with invoice prefix
        $resInvoice = $this->actingAs($this->admin)->get(route('admin.dashboard', ['q' => 'INV-202611-001']));
        $resInvoice->assertRedirect(route('admin.tagihan', ['q' => 'INV-202611-001']));

        // Direct submit with ticket prefix
        $resTicket = $this->actingAs($this->admin)->get(route('admin.dashboard', ['q' => 'TCK-001']));
        $resTicket->assertRedirect(route('admin.laporan', ['q' => 'TCK-001']));

        // Direct submit with order prefix
        $resOrder = $this->actingAs($this->admin)->get(route('admin.dashboard', ['q' => 'ORD-1234']));
        $resOrder->assertRedirect(route('admin.pesanan', ['q' => 'ORD-1234']));

        // Direct submit with customer name
        $resCustomer = $this->actingAs($this->admin)->get(route('admin.dashboard', ['q' => 'Budi']));
        $resCustomer->assertRedirect(route('admin.pelanggan', ['q' => 'Budi']));
    }
}
