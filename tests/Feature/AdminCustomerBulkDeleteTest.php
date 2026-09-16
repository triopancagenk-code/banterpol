<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCustomerBulkDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminUser(): User
    {
        return User::factory()->create([
            'role' => 'admin',
        ]);
    }

    private function createDirekturUser(): User
    {
        return User::factory()->create([
            'email' => 'direktur@banterpool.net',
            'name' => 'Direktur Utama',
            'role' => 'direktur',
        ]);
    }

    public function test_pelanggan_page_renders_selection_checkboxes(): void
    {
        $admin = $this->createAdminUser();

        Order::create([
            'order_number' => 'PLG-001',
            'customer_name' => 'Budi Santoso',
            'customer_phone' => '081234567890',
            'customer_email' => 'budi@gmail.com',
            'address' => 'Desa Bantarwuni RT 01/01',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'price' => 110000,
            'status' => 'Selesai',
        ]);

        $response = $this->actingAs($admin)->get('/admin/pelanggan');
        $response->assertStatus(200);
        $response->assertSee('Pilih Semua di Halaman Ini');
        $response->assertSee('Hapus Terpilih');
        $response->assertSee('toggleSelectAll');
        $response->assertSee('toggleSingle');
    }

    public function test_direktur_can_view_pelanggan_with_checkboxes(): void
    {
        $direktur = $this->createDirekturUser();

        Order::create([
            'order_number' => 'PLG-002',
            'customer_name' => 'Siti Aminah',
            'customer_phone' => '081234567891',
            'customer_email' => 'siti@gmail.com',
            'address' => 'Desa Linggasari RT 02/01',
            'package_name' => 'Paket 30 Mbps',
            'speed' => '30 Mbps',
            'price' => 165000,
            'status' => 'Selesai',
        ]);

        $response = $this->actingAs($direktur)->get('/admin/pelanggan');
        $response->assertStatus(200);
        $response->assertSee('Pilih Semua di Halaman Ini');
    }

    public function test_admin_can_bulk_delete_selected_customers(): void
    {
        $admin = $this->createAdminUser();

        $c1 = Order::create([
            'order_number' => 'PLG-101',
            'customer_name' => 'Pelanggan 1',
            'customer_phone' => '081234567890',
            'customer_email' => 'p1@gmail.com',
            'address' => 'Cilongok',
            'package_name' => 'Paket 20 Mbps',
            'status' => 'Selesai',
        ]);

        $c2 = Order::create([
            'order_number' => 'PLG-102',
            'customer_name' => 'Pelanggan 2',
            'customer_phone' => '081234567891',
            'customer_email' => 'p2@gmail.com',
            'address' => 'Cilongok',
            'package_name' => 'Paket 20 Mbps',
            'status' => 'Selesai',
        ]);

        $c3 = Order::create([
            'order_number' => 'PLG-103',
            'customer_name' => 'Pelanggan 3',
            'customer_phone' => '081234567892',
            'customer_email' => 'p3@gmail.com',
            'address' => 'Cilongok',
            'package_name' => 'Paket 20 Mbps',
            'status' => 'Selesai',
        ]);

        $this->assertEquals(3, Order::count());

        $response = $this->actingAs($admin)->post('/admin/pelanggan/bulk-delete', [
            'ids' => [$c1->id, $c2->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals(1, Order::count());
        $this->assertDatabaseMissing('orders', ['id' => $c1->id]);
        $this->assertDatabaseMissing('orders', ['id' => $c2->id]);
        $this->assertDatabaseHas('orders', ['id' => $c3->id]);
    }

    public function test_admin_can_delete_all_customers(): void
    {
        $admin = $this->createAdminUser();

        Order::create([
            'order_number' => 'PLG-201',
            'customer_name' => 'Pelanggan A',
            'customer_phone' => '081234567890',
            'customer_email' => 'pa@gmail.com',
            'address' => 'Cilongok',
            'package_name' => 'Paket 20 Mbps',
            'status' => 'Selesai',
        ]);

        Order::create([
            'order_number' => 'PLG-202',
            'customer_name' => 'Pelanggan B',
            'customer_phone' => '081234567891',
            'customer_email' => 'pb@gmail.com',
            'address' => 'Cilongok',
            'package_name' => 'Paket 20 Mbps',
            'status' => 'Selesai',
        ]);

        $response = $this->actingAs($admin)->post('/admin/pelanggan/bulk-delete', [
            'delete_all' => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals(0, Order::count());
    }
}
