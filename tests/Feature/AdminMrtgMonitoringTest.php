<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMrtgMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_mrtg_monitoring(): void
    {
        $response = $this->get('/admin/mrtg');
        $response->assertRedirect('/login');
    }

    public function test_customers_cannot_access_mrtg_monitoring(): void
    {
        $customer = User::factory()->create([
            'role' => 'pelanggan',
        ]);

        $response = $this->actingAs($customer)->get('/admin/mrtg');
        $response->assertForbidden();
    }

    public function test_admin_can_view_mrtg_grafana_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get('/admin/mrtg');
        $response->assertOk();
        $response->assertSee('Mikrotik monitoring');
        $response->assertSee('192.168.36.1');
        $response->assertSee('DS_PROMETHEUS');
        $response->assertSee('queuesimple_name');
        $response->assertSee('queuetree_name');
        $response->assertSee('CCR2116');
        $response->assertSee('Interfaces Table (SNMP MIKROTIK-MIB)');
        $response->assertSee('Simple Queue (In/Out/Dropped/PCQ)');
        $response->assertSee('Tree Queue (Bandwidth Allocation)');
        $response->assertSee('Neighbors Discovery (MNDP / LLDP)');
    }

    public function test_admin_can_filter_mrtg_dashboard_with_grafana_query_parameters(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get('/admin/mrtg?' . http_build_query([
            'orgId' => '1',
            'from' => 'now-30m',
            'to' => 'now',
            'timezone' => 'browser',
            'var-DS_PROMETHEUS' => 'dfs21jfqjnqbkd',
            'var-Job' => 'Mikrotik',
            'var-instance' => '192.168.36.1',
            'var-index' => '1',
            'var-Interface' => 'if-core-uplink',
            'var-desc' => '',
            'var-queuesimple_name' => '$__all',
            'var-queuetree_name' => '$__all',
            'var-queuetree_parent' => '$__all',
            'var-queuetree_flow' => '$__all',
            'refresh' => '10s',
        ]));

        $response->assertOk();
        $response->assertSee('Mikrotik monitoring');
        $response->assertSee('192.168.36.1');
        $response->assertSee('sfp-sfpplus1');
    }
}
