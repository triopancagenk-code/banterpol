<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCustomerFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $technician;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin.flow@banterpool.net',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->technician = User::factory()->create([
            'name' => 'Randi Teknisi',
            'email' => 'randi@banterpool.net',
            'role' => 'technician',
            'is_active' => true,
        ]);
    }

    public function test_new_order_enters_monitoring_pesanan_but_not_data_pelanggan_until_selesai(): void
    {
        // 1. Pelanggan memesan pemasangan baru (Status default: Menunggu Konfirmasi)
        $order = Order::create([
            'order_number' => 'ORD-20261001-001',
            'customer_name' => 'Alfi Pelanggan Baru',
            'customer_phone' => '081299990001',
            'customer_email' => 'alfi@example.com',
            'id_card_number' => '3302123456780001',
            'address' => 'Jl. Kenanga No. 10, Desa Panusupan, Kec. Cilongok',
            'village' => 'Panusupan',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'price' => 110000,
            'total' => 110000,
            'status' => 'Menunggu Konfirmasi',
            'payment_status' => 'Lunas',
        ]);

        // 2. Cek di menu Monitoring Pesanan: Pesanan HARUS ADA
        $responsePesanan = $this->actingAs($this->admin)->get(route('admin.pesanan'));
        $responsePesanan->assertStatus(200);
        $responsePesanan->assertSee('ORD-20261001-001');
        $responsePesanan->assertSee('Alfi Pelanggan Baru');
        $responsePesanan->assertSee('Menunggu Konfirmasi');
        $responsePesanan->assertSee('Belum Masuk Data Pelanggan');

        // 3. Cek di menu Data Pelanggan: Pesanan BELUM MASUK (status belum Selesai)
        $responsePelanggan = $this->actingAs($this->admin)->get(route('admin.pelanggan'));
        $responsePelanggan->assertStatus(200);
        $this->assertFalse($responsePelanggan->viewData('customers')->contains('order_number', 'ORD-20261001-001'));
        $this->assertFalse($responsePelanggan->viewData('customers')->contains('customer_name', 'Alfi Pelanggan Baru'));
        $this->assertEquals(0, $responsePelanggan->viewData('stats')['total']);

        // 4. Cek Live Autocomplete Data Pelanggan: Jangan munculkan pesanan yang belum selesai
        $responseAjax = $this->actingAs($this->admin)->get(route('admin.pelanggan', ['ajax' => 1, 'q' => 'Alfi']));
        $responseAjax->assertStatus(200);
        $responseAjax->assertJsonMissing(['customer_name' => 'Alfi Pelanggan Baru']);

        // 5. Cek Export Excel Data Pelanggan: Pesanan belum selesai tidak boleh ikut terekspor
        $responseExport = $this->actingAs($this->admin)->get(route('admin.pelanggan.export'));
        $responseExport->assertStatus(200);
        $this->assertStringNotContainsString('Alfi Pelanggan Baru', $responseExport->streamedContent());

        // 6. Admin memperbarui status ke 'Sedang Dipasang': Masih belum masuk ke Data Pelanggan
        $this->actingAs($this->admin)->post(route('admin.pesanan.status', $order->id), [
            'status' => 'Sedang Dipasang',
            'technician' => $this->technician->name,
        ]);

        $order->refresh();
        $this->assertEquals('Sedang Dipasang', $order->status);

        $responsePelangganStillNot = $this->actingAs($this->admin)->get(route('admin.pelanggan'));
        $responsePelangganStillNot->assertDontSee('Alfi Pelanggan Baru');

        // 7. Status diubah menjadi 'Selesai' (Pemasangan tuntas)
        $responseUpdate = $this->actingAs($this->admin)->post(route('admin.pesanan.status', $order->id), [
            'status' => 'Selesai',
        ]);
        $responseUpdate->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals('Selesai', $order->status);
        $this->assertNotNull($order->installed_at);

        // 8. Cek kembali Data Pelanggan: Pesanan SEKARANG SUDAH MASUK ke Data Pelanggan!
        $responsePelangganAfter = $this->actingAs($this->admin)->get(route('admin.pelanggan'));
        $responsePelangganAfter->assertStatus(200);
        $responsePelangganAfter->assertSee('ORD-20261001-001');
        $responsePelangganAfter->assertSee('Alfi Pelanggan Baru');
        $responsePelangganAfter->assertSee('3302123456780001');

        // 9. Cek Live Autocomplete Data Pelanggan: Sekarang Alfi sudah muncul
        $responseAjaxAfter = $this->actingAs($this->admin)->get(route('admin.pelanggan', ['ajax' => 1, 'q' => 'Alfi']));
        $responseAjaxAfter->assertStatus(200);
        $responseAjaxAfter->assertJsonFragment(['customer_name' => 'Alfi Pelanggan Baru']);

        // 10. Cek Export Excel: Sekarang Alfi ikut terekspor di Data Pelanggan
        $responseExportAfter = $this->actingAs($this->admin)->get(route('admin.pelanggan.export'));
        $this->assertStringContainsString('Alfi Pelanggan Baru', $responseExportAfter->streamedContent());

        // 11. Cek di Monitoring Pesanan: Label sudah berubah menjadi "Masuk Data Pelanggan"
        $responsePesananAfter = $this->actingAs($this->admin)->get(route('admin.pesanan'));
        $responsePesananAfter->assertSee('Masuk Data Pelanggan');
    }

    public function test_technician_completing_order_moves_it_to_data_pelanggan(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-20261001-002',
            'customer_name' => 'Bayu Pelanggan Teknisi',
            'customer_phone' => '081299990002',
            'customer_email' => 'bayu@example.com',
            'address' => 'Jl. Mawar No. 5, Panusupan',
            'package_name' => 'Paket 30 Mbps',
            'speed' => '30 Mbps',
            'price' => 165000,
            'total' => 165000,
            'status' => 'Jadwal Teknisi',
            'technician' => $this->technician->name,
            'technician_id' => $this->technician->id,
            'payment_status' => 'Lunas',
        ]);

        // Belum masuk ke data pelanggan
        $this->actingAs($this->admin)->get(route('admin.pelanggan'))->assertDontSee('Bayu Pelanggan Teknisi');

        // Teknisi menyelesaikan pemasangan di lapangan
        $this->actingAs($this->technician)->post(route('teknisi.pemasangan.status', $order->id), [
            'status' => 'Selesai',
            'ont_sn' => 'ZTEG99887766',
            'opm_dbm' => '-19.2',
            'technician_notes' => 'Pemasangan sukses, redaman bagus.',
        ]);

        $order->refresh();
        $this->assertEquals('Selesai', $order->status);
        $this->assertEquals('ZTEG99887766', $order->ont_sn);

        // Sekarang sudah masuk ke data pelanggan!
        $this->actingAs($this->admin)->get(route('admin.pelanggan'))->assertSee('Bayu Pelanggan Teknisi');
    }

    public function test_admin_can_delete_single_order_from_monitoring_pesanan(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-DEL-001',
            'customer_name' => 'Pesanan Dihapus',
            'customer_phone' => '081233334444',
            'customer_email' => 'del@example.com',
            'address' => 'Jl. Mawar No. 1, Panusupan',
            'package_name' => 'Paket 20 Mbps',
            'price' => 110000,
            'total' => 110000,
            'status' => 'Menunggu Konfirmasi',
            'payment_status' => 'Menunggu Pembayaran',
        ]);

        // Simulasikan jika ada tagihan terkait
        $bill = \App\Models\Bill::create([
            'order_id' => $order->id,
            'bill_number' => 'TAG-DEL-001',
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'customer_email' => 'del@example.com',
            'address' => 'Jl. Mawar No. 1, Panusupan',
            'package_name' => $order->package_name,
            'total' => $order->total,
            'status' => 'Belum Bayar',
            'period' => 'Oktober 2026',
            'bill_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(10)->format('Y-m-d'),
        ]);

        // Pastikan order dan tombol hapus tampil di monitoring pesanan
        $response = $this->actingAs($this->admin)->get(route('admin.pesanan'));
        $response->assertStatus(200);
        $response->assertSee('ORD-DEL-001');
        $response->assertSee('Pesanan Dihapus');
        $response->assertSee('Hapus Data Pesanan');

        // Admin menghapus pesanan
        $deleteResponse = $this->actingAs($this->admin)->delete(route('admin.pesanan.delete', $order->id));
        $deleteResponse->assertRedirect();
        $deleteResponse->assertSessionHas('success');

        // Pastikan order dan tagihan terhapus dari database
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertDatabaseMissing('bills', ['id' => $bill->id]);
    }

    public function test_admin_can_bulk_delete_orders_from_monitoring_pesanan(): void
    {
        $order1 = Order::create([
            'order_number' => 'ORD-BULK-001',
            'customer_name' => 'Pesanan Massal 1',
            'customer_phone' => '081233330001',
            'customer_email' => 'bulk1@example.com',
            'address' => 'Jl. Mawar No. 2, Panusupan',
            'package_name' => 'Paket 20 Mbps',
            'price' => 110000,
            'total' => 110000,
            'status' => 'Menunggu Konfirmasi',
        ]);

        $order2 = Order::create([
            'order_number' => 'ORD-BULK-002',
            'customer_name' => 'Pesanan Massal 2',
            'customer_phone' => '081233330002',
            'customer_email' => 'bulk2@example.com',
            'address' => 'Jl. Mawar No. 3, Panusupan',
            'package_name' => 'Paket 30 Mbps',
            'price' => 165000,
            'total' => 165000,
            'status' => 'Jadwal Teknisi',
        ]);

        $orderKeep = Order::create([
            'order_number' => 'ORD-BULK-003',
            'customer_name' => 'Pesanan Dipertahankan',
            'customer_phone' => '081233330003',
            'customer_email' => 'bulk3@example.com',
            'address' => 'Jl. Mawar No. 4, Panusupan',
            'package_name' => 'Paket 50 Mbps',
            'price' => 220000,
            'total' => 220000,
            'status' => 'Sedang Dipasang',
        ]);

        $bulkResponse = $this->actingAs($this->admin)->post(route('admin.pesanan.bulk-delete'), [
            'ids' => [$order1->id, $order2->id],
        ]);

        $bulkResponse->assertRedirect();
        $bulkResponse->assertSessionHas('success');

        $this->assertDatabaseMissing('orders', ['id' => $order1->id]);
        $this->assertDatabaseMissing('orders', ['id' => $order2->id]);
        $this->assertDatabaseHas('orders', ['id' => $orderKeep->id]);
    }

    public function test_customer_pov_shows_bill_when_order_status_is_selesai(): void
    {
        // 1. Pelanggan terdaftar login
        $customer = User::factory()->create([
            'name' => 'Pelanggan Root Test',
            'email' => 'pelanggan.root@test.com',
            'role' => 'customer',
            'phone' => '089876543210',
            'is_active' => true,
        ]);

        // 2. Pelanggan memesan layanan pemasangan WiFi (status awal: Menunggu Konfirmasi)
        $order = Order::create([
            'user_id' => $customer->id,
            'order_number' => 'ORD-20261001-999',
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,
            'address' => 'Jl. Pernasidi RT 01 RW 02, Cilongok',
            'village' => 'Pernasidi',
            'package_name' => 'Paket 30 Mbps',
            'speed' => '30 Mbps',
            'price' => 165000,
            'total' => 165000,
            'status' => 'Menunggu Konfirmasi',
            'payment_status' => 'Lunas',
        ]);

        // 3. Admin atau Teknisi memperbarui status pesanan menjadi 'Selesai'
        $responseUpdate = $this->actingAs($this->admin)->post(route('admin.pesanan.status', $order->id), [
            'status' => 'Selesai',
        ]);
        $responseUpdate->assertSessionHas('success');

        // 4. Pastikan tagihan otomatis terbit di database dengan user_id milik pelanggan
        $this->assertDatabaseHas('bills', [
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'customer_name' => $customer->name,
            'status' => 'Belum Bayar',
        ]);

        // 5. Pada POV Pelanggan (/tagihan), tagihan SEKARANG MUNCUL!
        $responseTagihanAfter = $this->actingAs($customer)->get(route('tagihan'));
        $responseTagihanAfter->assertStatus(200);
        $this->assertGreaterThan(0, count($responseTagihanAfter->viewData('bills')));
        $this->assertTrue($responseTagihanAfter->viewData('activeBill')['has_unpaid']);
        $responseTagihanAfter->assertSee('Paket 30 Mbps');
        $responseTagihanAfter->assertSee('165.000');
        $responseTagihanAfter->assertSee('Belum Bayar');
    }
}

