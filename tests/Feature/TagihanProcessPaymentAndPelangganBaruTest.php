<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Order;
use App\Models\User;
use App\Services\WhatsappGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TagihanProcessPaymentAndPelangganBaruTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminUser(): User
    {
        return User::factory()->create([
            'role' => 'admin',
        ]);
    }

    public function test_pay_tagihan_does_not_auto_send_whatsapp_by_default(): void
    {
        $admin = $this->createAdminUser();

        $bill = Bill::create([
            'bill_number' => 'INV-202611-101',
            'customer_name' => 'Rafi Test',
            'customer_phone' => '087722114769',
            'address' => 'Ajibarang, Banyumas',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'period' => '01 Nov 2026 – 30 Nov 2026',
            'due_date' => '20 Nov 2026',
            'bill_date' => '05 Nov 2026',
            'amount' => 110000,
            'total' => 110000,
            'status' => 'Belum Bayar',
        ]);

        // Mock WhatsApp Gateway agar memastikan sendSingle TIDAK dipanggil saat default bayar
        $gatewayMock = Mockery::mock(WhatsappGatewayService::class);
        $gatewayMock->shouldReceive('formatPhoneNumber')->andReturn('6287722114769');
        $gatewayMock->shouldNotReceive('sendSingle');
        $this->app->instance(WhatsappGatewayService::class, $gatewayMock);

        $response = $this->actingAs($admin)->post(route('admin.tagihan.pay', ['id' => $bill->bill_number]), [
            'payment_method' => 'Tunai / Cash (Kolektor Lapangan)',
            'collected_by'   => $admin->name,
            // send_wa TIDAK disertakan (default: tidak otomatis terkirim)
        ]);

        $response->assertSessionHas('wa_sent', false);
        $response->assertSessionHas('wa_message'); // Text tetap disiapkan untuk tombol Buka Chat WA
        $this->assertStringNotContainsString('Invoice otomatis berhasil dikirimkan ke WhatsApp', session('success') ?? '');

        $bill->refresh();
        $this->assertEquals('Lunas', $bill->status);
        $this->assertNotNull($bill->paid_at);
    }

    public function test_add_pelanggan_baru_with_lunas_status_automatically_enters_data_pelanggan(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->post(route('admin.tagihan.store'), [
            'customer_name' => 'Pelanggan Baru Lunas',
            'customer_phone' => '081234567899',
            'customer_email' => 'pelangganbaru@example.com',
            'address' => 'Desa Sudimara RT 01 RW 02, Banyumas',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'period' => '01 Nov 2026 – 30 Nov 2026',
            'due_date' => '20 Nov 2026',
            'bill_date' => '05 Nov 2026',
            'amount' => 110000,
            'tax' => 0,
            'total' => 110000,
            'status' => 'Lunas',
            'payment_method' => 'Tunai (Kolektor/Admin)',
        ]);

        $response->assertRedirect(route('admin.tagihan'));
        $response->assertSessionHas('success');

        // Pastikan Bill tersimpan dengan status Lunas
        $bill = Bill::where('customer_name', 'Pelanggan Baru Lunas')->first();
        $this->assertNotNull($bill);
        $this->assertEquals('Lunas', $bill->status);
        $this->assertNotNull($bill->order_id);

        // Pastikan otomatis dibuatkan record Order dengan status Selesai
        $order = Order::find($bill->order_id);
        $this->assertNotNull($order);
        $this->assertEquals('Pelanggan Baru Lunas', $order->customer_name);
        $this->assertEquals('Selesai', $order->status);
        $this->assertEquals('Lunas', $order->payment_status);
        $this->assertStringStartsWith('PLG-', $order->order_number);

        // Pastikan masuk ke query Data Pelanggan (scopeForCustomerData)
        $customerDataOrders = Order::forCustomerData()->get();
        $this->assertTrue($customerDataOrders->contains('id', $order->id));
    }

    public function test_pelanggan_baru_unpaid_enters_data_pelanggan_when_later_paid(): void
    {
        $admin = $this->createAdminUser();

        // 1. Tambah pelanggan baru dengan status Belum Bayar
        $this->actingAs($admin)->post(route('admin.tagihan.store'), [
            'customer_name' => 'Pelanggan Belum Lunas Awalnya',
            'customer_phone' => '089887766554',
            'customer_email' => 'belumlunas@example.com',
            'address' => 'Desa Ajibarang Kulon, Banyumas',
            'package_name' => 'Paket 30 Mbps',
            'speed' => '30 Mbps',
            'period' => '01 Nov 2026 – 30 Nov 2026',
            'due_date' => '20 Nov 2026',
            'bill_date' => '05 Nov 2026',
            'amount' => 165000,
            'tax' => 0,
            'total' => 165000,
            'status' => 'Belum Bayar',
        ]);

        $bill = Bill::where('customer_name', 'Pelanggan Belum Lunas Awalnya')->first();
        $this->assertNotNull($bill);
        $this->assertEquals('Belum Bayar', $bill->status);

        // 2. Lakukan Process Pembayaran Tagihan
        $gatewayMock = Mockery::mock(WhatsappGatewayService::class);
        $gatewayMock->shouldReceive('formatPhoneNumber')->andReturn('6289887766554');
        $this->app->instance(WhatsappGatewayService::class, $gatewayMock);

        $payResponse = $this->actingAs($admin)->post(route('admin.tagihan.pay', ['id' => $bill->bill_number]), [
            'payment_method' => 'Transfer Bank BCA',
            'collected_by'   => $admin->name,
        ]);

        $payResponse->assertSessionHas('success');

        // Pastikan sekarang sudah lunas dan otomatis terdaftar di Data Pelanggan
        $bill->refresh();
        $this->assertEquals('Lunas', $bill->status);
        $this->assertNotNull($bill->order_id);

        $order = Order::find($bill->order_id);
        $this->assertNotNull($order);
        $this->assertEquals('Selesai', $order->status);
        $this->assertStringStartsWith('PLG-', $order->order_number);

        $customerDataOrders = Order::forCustomerData()->get();
        $this->assertTrue($customerDataOrders->contains('id', $order->id));
    }
}

