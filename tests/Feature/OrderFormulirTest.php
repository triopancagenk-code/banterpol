<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderFormulirTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_formulir(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-20260910-861',
            'customer_name' => 'kasid',
            'customer_phone' => '0878987653',
            'customer_email' => 'kasid@example.com',
            'address' => 'Kasegeran, Banyumas, Central Java, 53161, Indonesia',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'price' => 110000,
            'installation_fee' => 0,
            'tax' => 0,
            'total' => 110000,
            'status' => 'Menunggu Konfirmasi',
            'payment_status' => 'Lunas',
        ]);

        $response = $this->get(route('admin.pesanan.formulir', $order->id));
        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_view_formulir_berlangganan_with_exact_template_elements(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@banterpool.net',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-20260910-861',
            'customer_name' => 'kasid',
            'customer_phone' => '0878987653',
            'customer_email' => 'kasid@example.com',
            'address' => 'Kasegeran, Banyumas, Central Java, 53161, Indonesia',
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
        // Header verification
        $response->assertSee('FORMULIR PENDAFTARAN');
        $response->assertSee('FIBER BROADBAND');
        $response->assertSee('FIBER BROADBAND REGISTRATION FORM');
        $response->assertSee('PT. Saga Infrastruktur Mediaselaras');
        $response->assertSee('Graha Mas Pemuda Rawamangun Blok AD 01');

        // Customer Data Section
        $response->assertSee('DATA PELANGGAN - Customer Data');
        $response->assertSee('Jenis pendaftaran -');
        $response->assertSee('Registration type');
        $response->assertSee('No. Pelanggan -');
        $response->assertSee('Customer ID');
        $response->assertSee('ORD-20260910-861');
        $response->assertSee('Nama pemilik rekening -');
        $response->assertSee('kasid');
        $response->assertSee('0878987653');
        $response->assertSee('Kasegeran, Banyumas');
        $response->assertSee('Up-To 20 Mbps');

        // Terms and conditions
        $response->assertSee('SYARAT DAN KETENTUAN');
        $response->assertSee('KETENTUAN BERLANGGANAN');
        $response->assertSee('TERMS &amp; CONDITIONS', false);
        $response->assertSee('Rp 500.000');

        // SIMS Completion Box & Signature
        $response->assertSee('Bagian ini diisi oleh SIMS');
        $response->assertSee('Biaya Registrasi');
        $response->assertSee('Biaya Bulanan');
        $response->assertSee('Tanda tangan - Signature');
    }

    public function test_monitoring_pesanan_has_formulir_print_link(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-20260910-861',
            'customer_name' => 'kasid',
            'customer_phone' => '0878987653',
            'customer_email' => 'kasid@example.com',
            'address' => 'Kasegeran, Banyumas, Central Java, 53161, Indonesia',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'price' => 110000,
            'installation_fee' => 0,
            'tax' => 0,
            'total' => 110000,
            'status' => 'Menunggu Konfirmasi',
            'payment_status' => 'Lunas',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.pesanan'));

        $response->assertStatus(200);
        $response->assertSee(route('admin.pesanan.formulir', $order->id));
        $response->assertSee('Formulir');
    }
}
