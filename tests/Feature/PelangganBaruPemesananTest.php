<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PelangganBaruPemesananTest extends TestCase
{
    use RefreshDatabase;

    private function createCustomerUser(): User
    {
        return User::factory()->create([
            'role' => 'user',
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '081298765432',
        ]);
    }

    public function test_checkout_page_displays_lanjut_button_and_correct_stepper(): void
    {
        $user = $this->createCustomerUser();

        $response = $this->actingAs($user)->get(route('checkout', [
            'name' => 'Budi Santoso',
            'phone' => '081298765432',
            'package_name' => 'Paket 20 Mbps',
            'package_price' => '110.000',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Lanjut');
        $response->assertDontSee('Bayar Sekarang');
        $response->assertSee('Setujui & Lanjutkan', false);
        $response->assertSee('Konfirmasi');
        $response->assertSee('Pesanan');
        $response->assertSee('Status');
        $response->assertSee('Pengajuan');
    }

    public function test_proceed_to_payment_status_creates_order_with_menunggu_konfirmasi_and_menunggu_pembayaran(): void
    {
        $user = $this->createCustomerUser();

        $response = $this->actingAs($user)->get(route('payment.status', [
            'name' => 'Budi Santoso',
            'phone' => '081298765432',
            'email' => 'budi@example.com',
            'id_card_number' => '3302010101900001',
            'address' => 'Jl. Merdeka No. 10, Purwokerto',
            'package_name' => 'Paket 20 Mbps',
            'package_speed' => '20 Mbps',
            'package_price' => '110.000',
            'installation_date' => now()->addDays(2)->format('Y-m-d'),
            'installation_time' => 'pagi',
        ]));

        $response->assertStatus(200);

        // Verify order created in database
        $order = Order::where('customer_phone', '081298765432')->latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals('Menunggu Konfirmasi', $order->status);
        $this->assertEquals('Menunggu Pembayaran', $order->payment_status);
        $this->assertStringStartsWith('ORD-', $order->order_number);

        // Verify view content
        $response->assertSee('Konfirmasi Pengajuan Pemesanan');
        $response->assertSee('Menunggu Konfirmasi Admin');
        $response->assertSee('Tidak Dikenakan Biaya Saat Ini');
        $response->assertSee($order->order_number);
    }

    public function test_direct_access_to_payment_redirects_to_confirmation(): void
    {
        $user = $this->createCustomerUser();

        $response = $this->actingAs($user)->get(route('payment', [
            'package_name' => 'Paket 20 Mbps',
            'package_price' => '110.000',
        ]));

        $response->assertRedirect();
        $this->assertStringContainsString('payment/status', $response->headers->get('Location'));
    }

    public function test_order_detail_page_shows_order_tracking_and_status(): void
    {
        $user = $this->createCustomerUser();

        $order = Order::create([
            'order_number' => 'ORD-20261113-777',
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_phone' => $user->phone,
            'customer_email' => $user->email,
            'address' => 'Jl. Gatot Subroto No. 5, Banyumas',
            'package_name' => 'Paket 30 Mbps',
            'speed' => '30 Mbps',
            'price' => 165000,
            'total' => 165000,
            'status' => 'Menunggu Konfirmasi',
            'payment_status' => 'Menunggu Pembayaran',
            'installation_date' => now()->addDays(3),
            'installation_time' => 'siang',
        ]);

        $response = $this->actingAs($user)->get(route('order.detail', [
            'order_number' => $order->order_number,
        ]));

        $response->assertStatus(200);
        $response->assertSee('ORD-20261113-777');
        $response->assertSee('Menunggu Konfirmasi Admin');
        $response->assertSee('Progres Pengajuan Layanan');
        $response->assertSee('Paket 30 Mbps');
        $response->assertDontSee('Pesan Layanan Lain');
    }

    public function test_user_with_pending_order_cannot_checkout_again(): void
    {
        $user = $this->createCustomerUser();

        $order = Order::create([
            'order_number' => 'ORD-20261113-111',
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_phone' => $user->phone,
            'customer_email' => $user->email,
            'address' => 'Jl. Test No. 1',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'price' => 110000,
            'total' => 110000,
            'status' => 'Menunggu Konfirmasi',
            'payment_status' => 'Menunggu Pembayaran',
        ]);

        $response = $this->actingAs($user)->get(route('checkout', [
            'package_name' => 'Paket 30 Mbps',
        ]));

        $response->assertRedirect(route('order.detail', ['order_number' => $order->order_number]));
        $response->assertSessionHas('warning');
    }

    public function test_user_with_active_order_cannot_checkout_again(): void
    {
        $user = $this->createCustomerUser();

        Order::create([
            'order_number' => 'ORD-20261113-222',
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_phone' => $user->phone,
            'customer_email' => $user->email,
            'address' => 'Jl. Test No. 2',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'price' => 110000,
            'total' => 110000,
            'status' => 'Selesai',
            'payment_status' => 'Lunas',
        ]);

        $response = $this->actingAs($user)->get(route('checkout', [
            'package_name' => 'Paket 50 Mbps',
        ]));

        $response->assertRedirect(route('tagihan'));
        $response->assertSessionHas('warning');
    }

    public function test_user_cannot_create_second_order_via_payment_status(): void
    {
        $user = $this->createCustomerUser();

        $order = Order::create([
            'order_number' => 'ORD-20261113-333',
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_phone' => $user->phone,
            'customer_email' => $user->email,
            'address' => 'Jl. Test No. 3',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'price' => 110000,
            'total' => 110000,
            'status' => 'Menunggu Konfirmasi',
            'payment_status' => 'Menunggu Pembayaran',
        ]);

        $response = $this->actingAs($user)->get(route('payment.status', [
            'name' => 'Budi Santoso',
            'address' => 'Jl. Test No. 3',
            'package_name' => 'Paket 50 Mbps',
        ]));

        $response->assertRedirect(route('order.detail', ['order_number' => $order->order_number]));
        $this->assertEquals(1, Order::where('user_id', $user->id)->count());
    }

    public function test_paket_page_does_not_show_warning_banner_notification(): void
    {
        $user = $this->createCustomerUser();

        Order::create([
            'order_number' => 'ORD-20261113-444',
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_phone' => $user->phone,
            'customer_email' => $user->email,
            'address' => 'Jl. Test No. 4',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'price' => 110000,
            'total' => 110000,
            'status' => 'Menunggu Konfirmasi',
            'payment_status' => 'Menunggu Pembayaran',
        ]);

        $response = $this->actingAs($user)->get(route('paket'));
        $response->assertStatus(200);
        $response->assertDontSee('Akun Anda Sudah Memiliki Layanan Terdaftar');
        $response->assertDontSee('1 Akun = 1 Layanan');
    }

    public function test_navigation_bar_hides_status_pesanan_for_new_customer_without_orders(): void
    {
        $user = $this->createCustomerUser();

        $response = $this->actingAs($user)->get(route('home'));
        $response->assertStatus(200);
        $response->assertDontSee('Status Pesanan');
    }

    public function test_navigation_bar_shows_status_pesanan_when_customer_has_pending_order(): void
    {
        $user = $this->createCustomerUser();

        Order::create([
            'order_number' => 'ORD-20261113-888',
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_phone' => $user->phone,
            'customer_email' => $user->email,
            'address' => 'Jl. Pemuda No. 12',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'price' => 110000,
            'total' => 110000,
            'status' => 'Menunggu Konfirmasi',
            'payment_status' => 'Menunggu Pembayaran',
        ]);

        $response = $this->actingAs($user)->get(route('home'));
        $response->assertStatus(200);
        $response->assertSee('Status Pesanan');
    }

    public function test_navigation_bar_hides_status_pesanan_when_order_is_completed_or_active(): void
    {
        $user = $this->createCustomerUser();

        Order::create([
            'order_number' => 'ORD-20261113-999',
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_phone' => $user->phone,
            'customer_email' => $user->email,
            'address' => 'Jl. Diponegoro No. 8',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'price' => 110000,
            'total' => 110000,
            'status' => 'Selesai',
            'payment_status' => 'Lunas',
        ]);

        $response = $this->actingAs($user)->get(route('home'));
        $response->assertStatus(200);
        $response->assertDontSee('Status Pesanan');
    }

    public function test_navigation_bar_shows_berlangganan_for_new_customer(): void
    {
        $user = $this->createCustomerUser();

        $response = $this->actingAs($user)->get(route('home'));
        $response->assertStatus(200);
        $response->assertSee(route('paket'));
        $response->assertSee('Berlangganan');
    }

    public function test_navigation_bar_hides_berlangganan_for_subscribed_customer(): void
    {
        $user = $this->createCustomerUser();

        Order::create([
            'order_number' => 'ORD-20261113-163',
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_phone' => $user->phone,
            'customer_email' => $user->email,
            'address' => 'Jl. Banyumas No. 1',
            'package_name' => 'Paket 30 Mbps',
            'speed' => '30 Mbps',
            'price' => 165000,
            'total' => 165000,
            'status' => 'Selesai',
            'payment_status' => 'Lunas',
        ]);

        $this->assertTrue($user->isSubscribed());

        // Di dashboard pelanggan (tagihan) maupun beranda, link menu 'Berlangganan' (route paket) tidak muncul
        $responseTagihan = $this->actingAs($user)->get(route('tagihan'));
        $responseTagihan->assertStatus(200);
        $responseTagihan->assertDontSee(route('paket'));

        $responseHome = $this->actingAs($user)->get(route('home'));
        $responseHome->assertStatus(200);
        $responseHome->assertDontSee(route('paket'));

        // Jika pelanggan yang sudah berlangganan mencoba membuka route paket langsung, diarahkan ke tagihan
        $responsePaket = $this->actingAs($user)->get(route('paket'));
        $responsePaket->assertRedirect(route('tagihan'));
    }

    public function test_navigation_bar_hides_berlangganan_for_customer_with_bills(): void
    {
        $user = $this->createCustomerUser();

        \App\Models\Bill::create([
            'bill_number' => 'INV-202611-999',
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_phone' => $user->phone,
            'address' => 'Jl. Banyumas No. 1',
            'package_name' => 'Paket 30 Mbps',
            'speed' => '30 Mbps',
            'period' => 'November 2026',
            'amount' => 165000,
            'status' => 'Lunas',
            'due_date' => now()->addDays(10),
        ]);

        $this->assertTrue($user->isSubscribed());

        $response = $this->actingAs($user)->get(route('tagihan'));
        $response->assertStatus(200);
        $response->assertDontSee(route('paket'));
    }
}
