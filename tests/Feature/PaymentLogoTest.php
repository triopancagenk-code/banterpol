<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentLogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_page_renders_with_local_svg_logos(): void
    {
        $user = User::factory()->create(['role' => 'pelanggan']);

        $response = $this->actingAs($user)->get('/payment');

        $response->assertStatus(200);
        $response->assertSee('image/banks/bca.svg');
        $response->assertSee('image/banks/mandiri.svg');
        $response->assertSee('image/banks/bni.svg');
        $response->assertSee('image/banks/bri.svg');
        $response->assertSee('image/banks/gopay.svg');
        $response->assertSee('image/banks/ovo.svg');
        $response->assertSee('image/banks/dana.svg');
        $response->assertDontSee('image/banks/shopeepay.svg');
        $response->assertSee('image/banks/qris.svg');
        $response->assertSee('image/banks/qris-code.svg');
        $response->assertSee('Scan QRIS');
        $response->assertDontSee('<span x-text="va.name"></span>', false);
        $response->assertDontSee('<span x-text="b.name"></span>', false);
        $response->assertDontSee('<span x-text="ew.name"></span>', false);
    }

    public function test_tagihan_payment_page_renders_with_local_svg_logos(): void
    {
        $user = User::factory()->create(['role' => 'pelanggan']);

        $response = $this->actingAs($user)->get('/tagihan/pembayaran?invoice=INV-202609-0001');

        $response->assertStatus(200);
        $response->assertSee('image/banks/bca.svg');
        $response->assertSee('image/banks/mandiri.svg');
        $response->assertSee('image/banks/bni.svg');
        $response->assertSee('image/banks/bri.svg');
        $response->assertSee('image/banks/gopay.svg');
        $response->assertSee('image/banks/ovo.svg');
        $response->assertSee('image/banks/dana.svg');
        $response->assertDontSee('image/banks/shopeepay.svg');
        $response->assertSee('image/banks/qris.svg');
        $response->assertSee('image/banks/qris-code.svg');
        $response->assertSee('Scan QRIS');
        $response->assertDontSee('<span x-text="key.toUpperCase() + \' VA\'"></span>', false);
        $response->assertDontSee('<span x-text="ewallet.name"></span>', false);
    }
}
