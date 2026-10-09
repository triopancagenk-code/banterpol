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
        $response->assertSee('image/banks/bri.svg');
        $response->assertSee('BRI Virtual Account');
        $response->assertSee('BRIVA');
    }

    public function test_tagihan_payment_page_renders_with_local_svg_logos(): void
    {
        $user = User::factory()->create(['role' => 'pelanggan']);

        $response = $this->actingAs($user)->get('/tagihan/pembayaran?invoice=INV-202609-0001');

        $response->assertStatus(200);
        $response->assertSee('image/banks/bri.svg');
        $response->assertSee('BRI Virtual Account');
        $response->assertSee('BRIVA');
    }
}
