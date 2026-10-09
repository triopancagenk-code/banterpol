<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCustomerPppoeTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin.pppoe@banterpool.net',
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_admin_pelanggan_displays_ppoe_under_customer_name(): void
    {
        $order = Order::create([
            'order_number' => 'PLG-2026-0001',
            'customer_name' => 'Sri Windi Astuti',
            'pppoe' => 'sriwindiastuti',
            'customer_email' => 'sri@gmail.com',
            'customer_phone' => '081463774771',
            'id_card_number' => '3302175808880002',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'price' => 150000,
            'address' => 'Panusupan RT 3 RW 6',
            'village' => 'Panusupan',
            'status' => 'Selesai',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.pelanggan'));

        $response->assertStatus(200);
        $response->assertSee('Sri Windi Astuti');
        $response->assertSee('PPOE:');
        $response->assertSee('sriwindiastuti');
        $response->assertSee('ppoe-' . $order->id);
    }

    public function test_ppoe_is_auto_generated_when_not_explicitly_set(): void
    {
        $order = Order::create([
            'order_number' => 'PLG-2026-0002',
            'customer_name' => 'Yachya Mutohir (SDN 3 Panusupan)',
            'customer_email' => 'yachya@gmail.com',
            'customer_phone' => '087794491452',
            'package_name' => 'Paket 50 Mbps',
            'speed' => '50 Mbps',
            'price' => 220000,
            'address' => 'Panusupan RT 6/5',
            'village' => 'Panusupan',
            'status' => 'Selesai',
        ]);

        $this->assertEquals('yachyamutohir', $order->pppoe);

        $response = $this->actingAs($this->admin)->get(route('admin.pelanggan'));

        $response->assertStatus(200);
        $response->assertSee('Yachya Mutohir (SDN 3 Panusupan)');
        $response->assertSee('yachyamutohir');
    }

    public function test_admin_can_update_customer_ppoe(): void
    {
        $order = Order::create([
            'order_number' => 'PLG-2026-0003',
            'customer_name' => 'Budi Sudarsono',
            'customer_email' => 'budi@gmail.com',
            'pppoe' => 'budisudarsono',
            'customer_phone' => '081234567890',
            'package_name' => 'Paket 20 Mbps',
            'price' => 110000,
            'address' => 'Panusupan RT 01 RW 01',
            'status' => 'Selesai',
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.pelanggan.update', $order->id), [
            'customer_name' => 'Budi Sudarsono',
            'customer_email' => 'budi@gmail.com',
            'pppoe' => 'budi_custom_ppoe',
            'package_name' => 'Paket 20 Mbps',
            'price' => 110000,
            'address' => 'Panusupan RT 01 RW 01',
            'status' => 'Selesai',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'pppoe' => 'budi_custom_ppoe',
        ]);

        $viewResponse = $this->actingAs($this->admin)->get(route('admin.pelanggan'));
        $viewResponse->assertSee('budi_custom_ppoe');
    }

    public function test_admin_can_search_customer_by_ppoe(): void
    {
        Order::create([
            'order_number' => 'PLG-2026-0004',
            'customer_name' => 'Arif Fatoni',
            'customer_email' => 'arif@gmail.com',
            'pppoe' => 'ariffatoni',
            'customer_phone' => '083852380763',
            'package_name' => 'Paket 20 Mbps',
            'price' => 165000,
            'address' => 'Sawangan Wetan',
            'village' => 'Sawangan',
            'status' => 'Selesai',
        ]);

        Order::create([
            'order_number' => 'PLG-2026-0005',
            'customer_name' => 'Ernawati',
            'customer_email' => 'ernawati@gmail.com',
            'pppoe' => 'ernawati',
            'customer_phone' => '085800090429',
            'package_name' => 'Paket 20 Mbps',
            'price' => 150000,
            'address' => 'Panusupan RT 6/1',
            'village' => 'Panusupan',
            'status' => 'Selesai',
        ]);

        $searchResponse = $this->actingAs($this->admin)->get(route('admin.pelanggan', ['q' => 'ariffatoni']));

        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('Arif Fatoni');
        $searchResponse->assertSee('ariffatoni');
        $searchResponse->assertDontSee('Ernawati');
    }
}
