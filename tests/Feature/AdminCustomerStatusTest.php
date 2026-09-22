<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCustomerStatusTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin.test@banterpool.net',
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_pelanggan_status_displays_aktif_badge(): void
    {
        Order::create([
            'order_number' => 'PLG-2026-0001',
            'customer_name' => 'Budi Pelanggan Aktif',
            'customer_email' => 'budi@gmail.com',
            'customer_phone' => '08123456789',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'price' => 110000,
            'address' => 'Panusupan RT 01 RW 01',
            'village' => 'Panusupan',
            'status' => 'Selesai',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.pelanggan'));

        $response->assertStatus(200);
        $response->assertSee('Budi Pelanggan Aktif');
        $response->assertSee('Aktif');
        $response->assertSee('<i class="fa-solid fa-circle text-[6px]"></i> Aktif', false);
    }

    public function test_pelanggan_filter_selesai_returns_active_customers(): void
    {
        Order::create([
            'order_number' => 'PLG-2026-0002',
            'customer_name' => 'Pelanggan Selesai',
            'customer_email' => 'selesai@gmail.com',
            'customer_phone' => '08123456781',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'price' => 110000,
            'address' => 'Panusupan RT 01 RW 01',
            'status' => 'Selesai',
        ]);

        Order::create([
            'order_number' => 'PLG-2026-0003',
            'customer_name' => 'Pelanggan Pasang',
            'customer_email' => 'pasang@gmail.com',
            'customer_phone' => '08123456782',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'price' => 110000,
            'address' => 'Panusupan RT 01 RW 02',
            'status' => 'Sedang Dipasang',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.pelanggan', ['status' => 'Selesai']));

        $response->assertStatus(200);
        $response->assertSee('Pelanggan Selesai');
        $response->assertDontSee('Pelanggan Pasang');
    }
}
