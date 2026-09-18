<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWilayahFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin.test@banterpool.net',
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_linggasari_filter_does_not_include_bantarwuni_kembaran(): void
    {
        // 1. Pelanggan Bantarwuni dengan keterangan Kembaran di alamat
        Order::create([
            'order_number' => 'PLG-TEST-BW01',
            'customer_name' => 'Nartim Bantarwuni',
            'customer_phone' => '089538238848',
            'customer_email' => '-',
            'address' => 'Bantarwuni (Kembaran) RT 04 / RW 04',
            'package_name' => '20 Mbps',
            'price' => 110000,
            'total' => 110000,
            'status' => 'Selesai',
        ]);

        // 2. Pelanggan resmi Linggasari
        Order::create([
            'order_number' => 'PLG-TEST-LIN01',
            'customer_name' => 'Erna Linggasari',
            'customer_phone' => '081326805577',
            'customer_email' => '-',
            'address' => 'KEMBARAN LINGGASARI, RT 04 RW 06',
            'package_name' => '20 Mbps',
            'price' => 110000,
            'total' => 110000,
            'status' => 'Selesai',
        ]);

        // Filter Linggasari
        $response = $this->actingAs($this->admin)->get(route('admin.pelanggan', ['wilayah' => 'Linggasari']));
        $response->assertStatus(200);
        $response->assertSee('Erna Linggasari');
        $response->assertDontSee('Nartim Bantarwuni');

        // Filter Bantarwuni
        $responseBw = $this->actingAs($this->admin)->get(route('admin.pelanggan', ['wilayah' => 'Bantarwuni']));
        $responseBw->assertStatus(200);
        $responseBw->assertSee('Nartim Bantarwuni');
        $responseBw->assertDontSee('Erna Linggasari');
    }

    public function test_filter_button_is_removed_and_dropdowns_submit_automatically(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.pelanggan'));
        $response->assertStatus(200);

        // Pastikan button 'Filter' sudah tidak ada
        $response->assertDontSee('Filter</button>', false);

        // Pastikan dropdown wilayah, layanan, dan status memiliki onchange="this.form.submit()"
        $response->assertSee('name="wilayah" onchange="this.form.submit()"', false);
        $response->assertSee('name="layanan" onchange="this.form.submit()"', false);
        $response->assertSee('name="status" onchange="this.form.submit()"', false);
    }
}

