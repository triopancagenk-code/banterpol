<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerRegistrationFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_registration_form_in_paket_has_ktp_and_ttl_inputs(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
        ]);

        $response = $this->actingAs($user)->get(route('paket'));

        $response->assertStatus(200);
        $response->assertSee('No. KTP (NIK)');
        $response->assertSee('name="id_card_number"', false);
        $response->assertSee('Tempat, Tanggal Lahir');
        $response->assertSee('name="birth_place"', false);
        $response->assertSee('name="birth_date"', false);
    }

    public function test_checkout_displays_customer_ktp_and_ttl(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
        ]);

        $response = $this->actingAs($user)->get(route('checkout', [
            'name' => 'Budi Santoso',
            'phone' => '081234567899',
            'email' => 'budi@example.com',
            'id_card_number' => '3302012304950001',
            'birth_place' => 'Banyumas',
            'birth_date' => '1995-04-23',
            'address' => 'Jl. Kenanga No. 10, Cilongok',
            'package_name' => 'Paket 20 Mbps',
            'package_price' => '110.000',
            'package_speed' => '20 Mbps',
        ]));

        $response->assertStatus(200);
        $response->assertSee('No. KTP (NIK)');
        $response->assertSee('3302012304950001');
        $response->assertSee('Tempat, Tanggal Lahir');
        $response->assertSee('Banyumas');
    }

    public function test_payment_status_saves_ktp_and_ttl_to_order(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
        ]);

        $response = $this->actingAs($user)->get(route('payment.status', [
            'status' => 'success',
            'name' => 'Budi Santoso',
            'phone' => '081234567899',
            'email' => 'budi@example.com',
            'id_card_number' => '3302012304950001',
            'birth_place' => 'Banyumas',
            'birth_date' => '1995-04-23',
            'address' => 'Jl. Kenanga No. 10, Cilongok',
            'package_name' => 'Paket 20 Mbps',
            'package_price' => '110000',
            'package_speed' => '20 Mbps',
        ]));

        $response->assertStatus(200);

        $order = Order::where('customer_name', 'Budi Santoso')->first();
        $this->assertNotNull($order);
        $this->assertEquals('3302012304950001', $order->id_card_number);
        $this->assertEquals('Banyumas', $order->birth_place);
        $this->assertEquals('1995-04-23', $order->birth_date->format('Y-m-d'));
    }

    public function test_admin_formulir_berlangganan_prefills_ktp_and_ttl(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-20260910-999',
            'customer_name' => 'Budi Santoso',
            'customer_phone' => '081234567899',
            'customer_email' => 'budi@example.com',
            'id_card_number' => '3302012304950001',
            'birth_place' => 'Banyumas',
            'birth_date' => '1995-04-23',
            'address' => 'Jl. Kenanga No. 10, Cilongok, Banyumas',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'price' => 110000,
            'installation_fee' => 0,
            'tax' => 0,
            'total' => 110000,
            'status' => 'Menunggu Konfirmasi',
            'payment_status' => 'Lunas',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.pesanan.formulir', $order->id));

        $response->assertStatus(200);
        $response->assertSee('3302012304950001');
        $response->assertSee('Banyumas');
        $response->assertSee('1995');
    }
}
