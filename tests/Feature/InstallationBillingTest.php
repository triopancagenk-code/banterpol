<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Order;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallationBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_technician_completing_installation_automatically_generates_bill_due_before_5th_of_next_month(): void
    {
        $technician = User::factory()->create([
            'role' => 'technician',
            'name' => 'Teknisi Test',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-' . date('Ymd') . '-9991',
            'customer_name' => 'Pelanggan Pemasangan Baru',
            'customer_phone' => '081299887766',
            'customer_email' => 'pasangbaru@example.com',
            'address' => 'Jl. Merdeka No. 10, Banyumas',
            'package_name' => 'Paket 50 Mbps',
            'speed' => '50 Mbps',
            'price' => 220000,
            'installation_fee' => 0,
            'tax' => 0,
            'total' => 220000,
            'status' => 'Sedang Dipasang',
        ]);

        $response = $this->actingAs($technician)->post(route('teknisi.pemasangan.status', $order->id), [
            'status' => 'Selesai',
            'technician_notes' => 'Pemasangan kabel dan ONT selesai.',
        ]);

        $response->assertRedirect();

        $order->refresh();
        $this->assertEquals('Selesai', $order->status);
        $this->assertNotNull($order->installed_at);

        $bill = Bill::where('order_id', $order->id)->first();
        $this->assertNotNull($bill);
        $this->assertEquals('Pelanggan Pemasangan Baru', $bill->customer_name);
        $this->assertEquals('Belum Bayar', $bill->status);
        $this->assertEquals(220000, (float) $bill->total);

        // Due date harus tanggal 5 bulan depan (format "05 Mmm YYYY")
        $expectedNextMonth = now('Asia/Jakarta')->addMonth()->translatedFormat('M Y');
        $this->assertStringContainsString('05', $bill->due_date);
        $this->assertStringContainsString($expectedNextMonth, $bill->due_date);
    }

    public function test_admin_completing_order_generates_bill(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-' . date('Ymd') . '-9992',
            'customer_name' => 'Pelanggan Admin Test',
            'customer_phone' => '081211223344',
            'customer_email' => 'admintest@example.com',
            'address' => 'Jl. Sudirman No. 45, Banyumas',
            'package_name' => 'Paket 30 Mbps',
            'speed' => '30 Mbps',
            'price' => 165000,
            'total' => 165000,
            'status' => 'Jadwal Teknisi',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.pesanan.status', $order->id), [
            'status' => 'Selesai',
        ]);

        $response->assertRedirect();

        $bill = Bill::where('order_id', $order->id)->first();
        $this->assertNotNull($bill);
        $this->assertEquals('Pelanggan Admin Test', $bill->customer_name);
        $this->assertEquals(165000, (float) $bill->total);
        $this->assertStringStartsWith('05', $bill->due_date);
    }

    public function test_bill_appears_in_collector_pov(): void
    {
        $collector = User::factory()->create([
            'role' => 'collector',
            'name' => 'Kolektor Lapangan',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-' . date('Ymd') . '-9993',
            'customer_name' => 'Bpk. Hendra Kolektor',
            'customer_phone' => '081399887711',
            'customer_email' => 'hendrakolektor@example.com',
            'address' => 'Desa Kasegeran RT 01/02, Banyumas',
            'package_name' => 'Paket 50 Mbps',
            'speed' => '50 Mbps',
            'price' => 220000,
            'total' => 220000,
            'status' => 'Selesai',
            'installed_at' => now('Asia/Jakarta'),
        ]);

        $bill = BillingService::generateBillForOrder($order);

        $response = $this->actingAs($collector)->get(route('kolektor.tagihan'));
        $response->assertOk();
        $response->assertSee($bill->bill_number);
        $response->assertSee('Bpk. Hendra Kolektor');
        $response->assertSee('220.000');
        $response->assertSee($bill->due_date);
    }

    public function test_bill_appears_in_customer_pov(): void
    {
        $customer = User::factory()->create([
            'name' => 'Trio Panca Test',
            'email' => 'triotest@banterpool.com',
            'phone' => '08123456789',
            'role' => 'customer',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-' . date('Ymd') . '-9994',
            'customer_name' => 'Trio Panca Test',
            'customer_phone' => '08123456789',
            'customer_email' => 'triotest@banterpool.com',
            'address' => 'Kasegeran, Banyumas',
            'package_name' => 'Paket 50 Mbps',
            'speed' => '50 Mbps',
            'price' => 220000,
            'total' => 220000,
            'status' => 'Selesai',
            'installed_at' => now('Asia/Jakarta'),
        ]);

        $bill = BillingService::generateBillForOrder($order);

        $response = $this->actingAs($customer)->get(route('tagihan'));
        $response->assertOk();
        $response->assertSee($bill->bill_number);
        $response->assertSee('Paket 50 Mbps');
        $response->assertSee('220.000');
        $response->assertSee($bill->due_date);
    }

    public function test_billing_generation_is_idempotent(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-' . date('Ymd') . '-9995',
            'customer_name' => 'Pelanggan Idempoten',
            'customer_phone' => '081233445566',
            'customer_email' => 'idempoten@example.com',
            'address' => 'Cilongok, Banyumas',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'price' => 110000,
            'total' => 110000,
            'status' => 'Selesai',
            'installed_at' => now('Asia/Jakarta'),
        ]);

        $bill1 = BillingService::generateBillForOrder($order);
        $bill2 = BillingService::generateBillForOrder($order);

        $this->assertEquals($bill1->id, $bill2->id);
        $this->assertEquals($bill1->bill_number, $bill2->bill_number);
        $this->assertEquals(1, Bill::where('order_id', $order->id)->count());
    }

    public function test_all_bills_due_dates_fall_on_the_5th(): void
    {
        // Buat beberapa tagihan simulasi dengan variasi due_date
        $bill1 = Bill::create([
            'bill_number' => 'INV-202609-881',
            'customer_name' => 'Pelanggan A',
            'customer_phone' => '081234567891',
            'address' => 'Banyumas',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'period' => '25 Agu 2026 – 25 Sep 2026',
            'due_date' => '25 Agu 2026',
            'bill_date' => '25 Agu 2026',
            'amount' => 110000,
            'tax' => 0,
            'total' => 110000,
            'status' => 'Jatuh Tempo',
        ]);

        $bill2 = Bill::create([
            'bill_number' => 'INV-202609-882',
            'customer_name' => 'Pelanggan B',
            'customer_phone' => '081234567892',
            'address' => 'Banyumas',
            'package_name' => 'Paket 30 Mbps',
            'speed' => '30 Mbps',
            'period' => '06 Sep 2026 – 06 Okt 2026',
            'due_date' => '06 Sep 2026',
            'bill_date' => '06 Sep 2026',
            'amount' => 165000,
            'tax' => 0,
            'total' => 165000,
            'status' => 'Belum Bayar',
        ]);

        BillingService::normalizeAllBillDueDates();

        $bill1->refresh();
        $bill2->refresh();

        $this->assertStringStartsWith('05 ', $bill1->due_date);
        $this->assertStringStartsWith('05 ', $bill2->due_date);

        // Pastikan setiap tagihan di database tanggal jatuh temponya dimulai dengan '05'
        foreach (Bill::all() as $b) {
            $this->assertMatchesRegularExpression('/^0?5\s+/i', $b->due_date);
        }
    }
}
