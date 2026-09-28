<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTechnicianSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_store_pesanan_manual_without_filling_status(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@banterpool.net',
        ]);

        $tech = User::factory()->create([
            'name' => 'Budi Santoso',
            'role' => 'technician',
            'email' => 'budi@banterpool.net',
        ]);

        // 1. Tambah pesanan manual tanpa input status dan tanpa teknisi -> Menunggu Konfirmasi
        $response1 = $this->actingAs($admin)->post(route('admin.pesanan.store'), [
            'customer_name' => 'Pelanggan Uji Satu',
            'customer_phone' => '081234567890',
            'customer_email' => 'pelanggan1@example.com',
            'address' => 'Jl. Cilongok Raya No. 12',
            'package_name' => 'Paket 20 Mbps',
            'price' => 110000,
            'payment_status' => 'Lunas',
            'payment_method' => 'BCA Virtual Account',
        ]);

        $response1->assertRedirect(route('admin.pesanan'));
        $order1 = Order::where('customer_name', 'Pelanggan Uji Satu')->first();
        $this->assertNotNull($order1);
        $this->assertEquals('Menunggu Konfirmasi', $order1->status);

        // 2. Tambah pesanan manual tanpa input status tapi dengan teknisi terpilih -> Jadwal Teknisi
        $response2 = $this->actingAs($admin)->post(route('admin.pesanan.store'), [
            'customer_name' => 'Pelanggan Uji Dua',
            'customer_phone' => '081234567899',
            'customer_email' => 'pelanggan2@example.com',
            'address' => 'Jl. Pernasidi No. 5',
            'package_name' => 'Paket 30 Mbps',
            'price' => 165000,
            'technician' => $tech->name,
            'payment_status' => 'Menunggu Pembayaran',
            'payment_method' => 'Transfer Bank (BCA)',
        ]);

        $response2->assertRedirect(route('admin.pesanan'));
        $order2 = Order::where('customer_name', 'Pelanggan Uji Dua')->first();
        $this->assertNotNull($order2);
        $this->assertEquals('Jadwal Teknisi', $order2->status);
        $this->assertEquals($tech->id, $order2->technician_id);
    }

    public function test_direktur_can_view_pesanan_and_technician_sync(): void
    {
        $direktur = User::factory()->create([
            'role' => 'direktur',
            'email' => 'direktur@banterpool.net',
        ]);

        $order = Order::create([
            'order_number' => 'BTR-202609-0001',
            'customer_name' => 'Ahmad Warga',
            'customer_phone' => '081234567891',
            'customer_email' => 'ahmad@example.com',
            'address' => 'Desa Cilongok',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'price' => 110000,
            'total' => 110000,
            'status' => 'Sedang Dipasang',
            'payment_status' => 'Lunas',
            'technician' => 'Budi Santoso',
            'ont_sn' => 'ZTEG-1234ABCD',
            'opm_dbm' => '-18.5',
            'technician_notes' => 'Tarik drop core 150 meter ke tiang ODP-CLK-01',
        ]);

        $response = $this->actingAs($direktur)->get(route('admin.pesanan'));
        $response->assertStatus(200);
        $response->assertSee('Sedang Dipasang');
        $response->assertSee('BTR-202609-0001');
        $response->assertSee('Ahmad Warga');
    }

    public function test_technician_status_update_is_reflected_on_admin_and_preserved_when_admin_edits(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@banterpool.net',
        ]);

        $tech = User::factory()->create([
            'name' => 'Mamat Teknisi',
            'role' => 'technician',
            'email' => 'mamat@banterpool.net',
        ]);

        $order = Order::create([
            'order_number' => 'BTR-202609-0002',
            'customer_name' => 'Siti Aminah',
            'customer_phone' => '081234567892',
            'customer_email' => 'siti@example.com',
            'address' => 'Desa Cilongok',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'price' => 110000,
            'total' => 110000,
            'status' => 'Jadwal Teknisi',
            'payment_status' => 'Lunas',
            'technician' => $tech->name,
            'technician_id' => $tech->id,
        ]);

        // 1. Teknisi mengupdate status pemasangan menjadi 'Kendala Lapangan'
        $responseTech = $this->actingAs($tech)->post(url('/teknisi/pemasangan/' . $order->id . '/status'), [
            'status' => 'Kendala Lapangan',
            'assigned_odp' => 'ODP-CLK-02',
            'technician_notes' => 'Port ODP penuh, menunggu ekspansi splitter',
        ]);

        $responseTech->assertRedirect();
        $order->refresh();
        $this->assertEquals('Kendala Lapangan', $order->status);
        $this->assertEquals('Port ODP penuh, menunggu ekspansi splitter', $order->technician_notes);

        // 2. Admin membuka POV Admin dan melihat Kendala Lapangan
        $responseAdmin = $this->actingAs($admin)->get(route('admin.pesanan'));
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertSee('Kendala Lapangan');

        // 3. Admin mengupdate catatan admin dan payment_status tanpa mengirim field 'status'
        $responseUpdate = $this->actingAs($admin)->post(route('admin.pesanan.status', $order->id), [
            'payment_status' => 'Lunas',
            'admin_notes' => 'Sudah dikonfirmasi ke bagian NOC terkait ODP penuh',
        ]);

        $responseUpdate->assertRedirect();
        $order->refresh();
        // Status lapangan dari teknisi tetap aman 'Kendala Lapangan'
        $this->assertEquals('Kendala Lapangan', $order->status);
        $this->assertEquals('Sudah dikonfirmasi ke bagian NOC terkait ODP penuh', $order->admin_notes);
    }
}
