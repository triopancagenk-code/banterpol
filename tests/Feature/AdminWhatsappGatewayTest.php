<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Order;
use App\Models\User;
use App\Models\WhatsappLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWhatsappGatewayTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminUser(): User
    {
        return User::factory()->create([
            'role' => 'admin',
        ]);
    }

    private function createCustomerUser(): User
    {
        return User::factory()->create([
            'role' => 'customer',
        ]);
    }

    public function test_guests_and_customers_cannot_access_whatsapp_gateway(): void
    {
        // Tamu tanpa login
        $this->get('/admin/whatsapp-gateway')->assertRedirect('/login');
        $this->post('/admin/whatsapp-gateway/broadcast')->assertRedirect('/login');

        // Pelanggan reguler
        $customer = $this->createCustomerUser();
        $this->actingAs($customer)->get('/admin/whatsapp-gateway')->assertForbidden();
        $this->actingAs($customer)->post('/admin/whatsapp-gateway/broadcast')->assertForbidden();
    }

    public function test_admin_can_view_whatsapp_gateway_page(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get('/admin/whatsapp-gateway');
        $response->assertOk();
        $response->assertSee('WhatsApp Gateway & Broadcast', false);
        $response->assertSee('Form Broadcast WhatsApp Massal', false);
        $response->assertSee('Banterpool NOC Bot', false);
        $response->assertSee('0881-8679-774', false);
    }

    public function test_admin_can_send_broadcast_to_all_customers(): void
    {
        $admin = $this->createAdminUser();

        // Buat data order & bill
        Order::create([
            'order_number' => 'ORD-20260907-001',
            'customer_name' => 'Budi Santoso',
            'customer_phone' => '081234567890',
            'customer_email' => 'budi@example.com',
            'address' => 'Jl. Raya Pernasidi No. 45, Cilongok',
            'package_name' => 'Paket 50 Mbps',
            'status' => 'Selesai',
        ]);

        $response = $this->actingAs($admin)->post('/admin/whatsapp-gateway/broadcast', [
            'target_filter' => 'all',
            'message_type' => 'maintenance',
            'message' => 'Halo {nama}, kami informasikan pemeliharaan jaringan Banterpool.',
        ]);

        $response->assertRedirect(route('admin.whatsapp', ['tab' => 'logs']));
        $response->assertSessionHas('success');

        // Verifikasi log tercatat di database
        $this->assertDatabaseHas('whatsapp_logs', [
            'recipient_name' => 'Budi Santoso',
            'recipient_phone' => '6281234567890',
            'message_type' => 'maintenance',
            'status' => 'sent',
        ]);
    }

    public function test_admin_can_send_broadcast_with_unpaid_target_filter(): void
    {
        $admin = $this->createAdminUser();

        Bill::create([
            'bill_number' => 'INV-202609-991',
            'customer_name' => 'Siti Khadijah',
            'customer_phone' => '085811223344',
            'customer_email' => 'siti@example.com',
            'address' => 'Desa Karanglo RT 02, Cilongok',
            'package_name' => 'Paket 20 Mbps',
            'period' => '01 Sep 2026 – 01 Okt 2026',
            'due_date' => '05 Sep 2026',
            'status' => 'Belum Bayar',
            'total' => 222000,
        ]);

        $response = $this->actingAs($admin)->post('/admin/whatsapp-gateway/broadcast', [
            'target_filter' => 'unpaid',
            'message_type' => 'billing',
            'message' => 'Halo {nama}, tagihan {paket} Anda sebesar {tagihan} segera jatuh tempo.',
        ]);

        $response->assertRedirect(route('admin.whatsapp', ['tab' => 'logs']));
        $this->assertDatabaseHas('whatsapp_logs', [
            'recipient_name' => 'Siti Khadijah',
            'recipient_phone' => '6285811223344',
            'message_type' => 'billing',
        ]);
    }

    public function test_admin_can_send_single_whatsapp_message(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->post('/admin/whatsapp-gateway/send-single', [
            'phone' => '087788990011',
            'name' => 'Pak Lurah Pernasidi',
            'message' => 'Selamat siang Pak Lurah, koneksi WiFi kantor desa sudah aktif.',
            'message_type' => 'single',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('whatsapp_logs', [
            'recipient_name' => 'Pak Lurah Pernasidi',
            'recipient_phone' => '6287788990011',
            'status' => 'sent',
        ]);
    }

    public function test_whatsapp_logs_can_be_deleted_and_cleared(): void
    {
        $admin = $this->createAdminUser();

        $log = WhatsappLog::create([
            'recipient_name' => 'Tester Log',
            'recipient_phone' => '6281122334455',
            'message_type' => 'custom',
            'message' => 'Pesan uji coba',
            'status' => 'sent',
        ]);

        $this->assertDatabaseHas('whatsapp_logs', ['id' => $log->id]);

        // Hapus single log
        $response = $this->actingAs($admin)->delete("/admin/whatsapp-gateway/logs/{$log->id}");
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('whatsapp_logs', ['id' => $log->id]);

        // Buat log baru lalu bersihkan
        WhatsappLog::create([
            'recipient_name' => 'Tester 2',
            'recipient_phone' => '6281122334466',
            'message' => 'Pesan test 2',
        ]);
        $this->assertEquals(1, WhatsappLog::count());

        $this->actingAs($admin)->post('/admin/whatsapp-gateway/logs/clear');
        $this->assertEquals(0, WhatsappLog::count());
    }

    public function test_admin_can_export_whatsapp_logs_excel(): void
    {
        $admin = $this->createAdminUser();

        WhatsappLog::create([
            'recipient_name' => 'Penerima Excel',
            'recipient_phone' => '628991234567',
            'message_type' => 'promo',
            'message' => 'Promo upgrade paket internet.',
            'status' => 'sent',
        ]);

        $response = $this->actingAs($admin)->get('/admin/whatsapp-gateway/logs/export');
        $response->assertOk();
        $this->assertStringContainsString('application/vnd.ms-excel', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Penerima Excel', $response->streamedContent());
    }

    public function test_admin_can_save_whatsapp_settings(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->post('/admin/whatsapp-gateway/settings', [
            'provider' => 'Fonnte',
            'sender_number' => '081234567890',
        ]);

        $response->assertRedirect(route('admin.whatsapp', ['tab' => 'settings']));
        $response->assertSessionHas('success');
    }
}
