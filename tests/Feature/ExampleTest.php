<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_customer_view_renders_animated_wave_background_and_separate_white_footer(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('bg-wave-container');
        $response->assertSee('bgWavePattern');
        $response->assertSee('bgWaveGlow');
        $response->assertSee('renderWave');
        $response->assertSee('relative z-20 bg-white border-t border-gray-200');
    }
}
