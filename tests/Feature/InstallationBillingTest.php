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

    public function test_subscribing_immediately_generates_next_month_bill_due_on_the_5th(): void
    {
        $customer = User::factory()->create([
            'name' => 'Pelanggan Baru Daftar',
            'email' => 'barudaftar@banterpool.com',
            'phone' => '089988776655',
            'role' => 'customer',
        ]);

        // Simulasi pelanggan mendaftar langganan melalui alur website (Order status masih Menunggu Konfirmasi)
        $order = Order::create([
            'order_number' => 'ORD-' . date('Ymd') . '-7781',
            'user_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,
            'address' => 'Desa Karanganyar, Banyumas',
            'package_name' => 'Paket 30 Mbps',
            'speed' => '30 Mbps',
            'price' => 165000,
            'total' => 165000,
            'status' => 'Menunggu Konfirmasi',
        ]);

        // Kunjungi halaman tagihan dari POV pelanggan
        $response = $this->actingAs($customer)->get(route('tagihan'));
        $response->assertOk();

        // Tagihan bulan depan harus langsung ada di database dan tampil di halaman tagihan
        $bill = Bill::where('order_id', $order->id)->first();
        $this->assertNotNull($bill);
        $this->assertEquals('Belum Bayar', $bill->status);
        $this->assertEquals(165000, (float) $bill->total);

        // Jatuh tempo harus tanggal 5 bulan depan
        $expectedNextMonth = now('Asia/Jakarta')->addMonth()->translatedFormat('M Y');
        $this->assertStringStartsWith('05', $bill->due_date);
        $this->assertStringContainsString($expectedNextMonth, $bill->due_date);

        $response->assertSee($bill->bill_number);
        $response->assertSee('Paket 30 Mbps');
        $response->assertSee('165.000');
        $response->assertSee($bill->due_date);
    }

    public function test_paying_bill_automatically_generates_subsequent_month_bill_due_on_the_5th(): void
    {
        $customer = User::factory()->create([
            'name' => 'Pelanggan Bayar Berulang',
            'email' => 'bayarberulang@banterpool.com',
            'role' => 'customer',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-' . date('Ymd') . '-7782',
            'user_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => '081234567800',
            'address' => 'Banyumas',
            'package_name' => 'Paket 50 Mbps',
            'speed' => '50 Mbps',
            'price' => 220000,
            'total' => 220000,
            'status' => 'Selesai',
        ]);

        // Tagihan pertama terbit (misal bulan depan)
        $firstBill = BillingService::generateBillForOrder($order);
        $this->assertEquals('Belum Bayar', $firstBill->status);

        // Pelanggan membayar tagihan pertama via konfirmasi pembayaran
        $response = $this->actingAs($customer)->post(route('tagihan.payment.confirm'), [
            'invoice' => $firstBill->bill_number,
            'payment_method' => 'Transfer Bank (BCA)',
        ]);

        $response->assertRedirect(route('tagihan'));

        // Tagihan pertama sekarang berstatus Lunas
        $firstBill->refresh();
        $this->assertEquals('Lunas', $firstBill->status);
        $this->assertNotNull($firstBill->paid_at);

        // Tagihan untuk bulan selanjutnya otomatis terbit dengan jatuh tempo tanggal 5
        $nextBill = Bill::where('order_id', $order->id)
            ->where('id', '!=', $firstBill->id)
            ->first();

        $this->assertNotNull($nextBill);
        $this->assertEquals('Belum Bayar', $nextBill->status);
        $this->assertStringStartsWith('05', $nextBill->due_date);

        // Jatuh tempo tagihan kedua harus 1 bulan setelah jatuh tempo tagihan pertama
        $firstDueCarbon = BillingService::parseDueDate($firstBill->due_date);
        $expectedSecondDueMonth = $firstDueCarbon->copy()->addMonth()->translatedFormat('M Y');
        $this->assertStringContainsString($expectedSecondDueMonth, $nextBill->due_date);

        // Di halaman tagihan pelanggan, tagihan kedua muncul sebagai tagihan aktif belum bayar
        $tagihanPage = $this->actingAs($customer)->get(route('tagihan'));
        $tagihanPage->assertOk();
        $tagihanPage->assertSee($nextBill->bill_number);
        $tagihanPage->assertSee($nextBill->due_date);

        // Dan tagihan pertama yang lunas muncul di riwayat
        $tagihanPage->assertSee($firstBill->bill_number);
    }

    public function test_customer_tagihan_view_renders_table_and_tabs_matching_admin_and_direktur_structure(): void
    {
        $customer = User::factory()->create([
            'name' => 'Bambang Sudarmono',
            'email' => 'bambang@banterpool.com',
            'role' => 'customer',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-' . date('Ymd') . '-8888',
            'customer_name' => 'Bambang Sudarmono',
            'customer_phone' => '081299887766',
            'customer_email' => 'bambang@banterpool.com',
            'address' => 'Jl. Garuda No. 10, Cilongok',
            'package_name' => 'Paket 50 Mbps',
            'speed' => '50 Mbps',
            'price' => 220000,
            'total' => 220000,
            'status' => 'Selesai',
        ]);

        $bill1 = Bill::create([
            'bill_number' => 'INV-202609-0088',
            'order_id' => $order->id,
            'customer_name' => 'Bambang Sudarmono',
            'customer_phone' => '081299887766',
            'customer_email' => 'bambang@banterpool.com',
            'address' => 'Jl. Garuda No. 10, Cilongok',
            'package_name' => 'Paket 50 Mbps',
            'speed' => '50 Mbps',
            'period' => '01 Sep 2026 – 01 Okt 2026',
            'due_date' => '05 Okt 2026',
            'bill_date' => '28 Sep 2026',
            'amount' => 220000,
            'tax' => 0,
            'total' => 220000,
            'status' => 'Belum Bayar',
            'payment_method' => 'Transfer Bank (BCA)',
        ]);

        $bill2 = Bill::create([
            'bill_number' => 'INV-202608-0088',
            'order_id' => $order->id,
            'customer_name' => 'Bambang Sudarmono',
            'customer_phone' => '081299887766',
            'customer_email' => 'bambang@banterpool.com',
            'address' => 'Jl. Garuda No. 10, Cilongok',
            'package_name' => 'Paket 50 Mbps',
            'speed' => '50 Mbps',
            'period' => '01 Agu 2026 – 01 Sep 2026',
            'due_date' => '05 Sep 2026',
            'bill_date' => '28 Agu 2026',
            'amount' => 220000,
            'tax' => 0,
            'total' => 220000,
            'status' => 'Lunas',
            'payment_method' => 'Transfer Bank (BCA)',
            'paid_at' => now(),
        ]);

        // 1. POV Pelanggan melihat halaman tagihan
        $response = $this->actingAs($customer)->get(route('tagihan'));
        $response->assertOk();

        // Verifikasi Tabs Status yang serupa dengan POV Admin & Direktur
        $response->assertSee('Semua');
        $response->assertSee('Menunggu Verifikasi');
        $response->assertSee('Belum Bayar');
        $response->assertSee('Lunas');
        $response->assertSee('Jatuh Tempo');

        // Verifikasi Kolom Tabel
        $response->assertSee('Invoice & Tanggal', false);
        $response->assertSee('Paket & Kecepatan', false);
        $response->assertSee('Periode Berlangganan');
        $response->assertSee('Jatuh Tempo');
        $response->assertSee('Total Tagihan');
        $response->assertSee('Metode Bayar');
        $response->assertSee('Status');
        $response->assertSee('Aksi');

        // Verifikasi Data Tagihan Tampil
        $response->assertSee('INV-202609-0088');
        $response->assertSee('INV-202608-0088');
        $response->assertSee('Paket 50 Mbps');
        $response->assertSee('220.000');

        // 2. Uji Filter Status Lunas
        $responseFiltered = $this->actingAs($customer)->get(route('tagihan', ['status' => 'Lunas']));
        $responseFiltered->assertOk();
        $responseFiltered->assertSee('INV-202608-0088');
        $filteredBills = $responseFiltered->viewData('bills');
        $this->assertCount(1, $filteredBills);
        $this->assertEquals('INV-202608-0088', $filteredBills[0]['id']);

        // 3. Uji Pencarian Invoice
        $responseSearch = $this->actingAs($customer)->get(route('tagihan', ['q' => 'INV-202609']));
        $responseSearch->assertOk();
        $responseSearch->assertSee('INV-202609-0088');
        $searchBills = $responseSearch->viewData('bills');
        $this->assertCount(1, $searchBills);
        $this->assertEquals('INV-202609-0088', $searchBills[0]['id']);
    }

    public function test_bill_does_not_appear_in_admin_monitoring_tagihan_until_order_is_completed(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        // 1. Pesanan pemasangan dengan status "Sedang Dipasang"
        $inProgressOrder = Order::create([
            'order_number' => 'ORD-20260925-547',
            'customer_name' => 'Rafi Razani In Progress',
            'customer_phone' => '081234567890',
            'customer_email' => 'rafi.progress@example.com',
            'address' => 'Jl. Pemasangan No. 12',
            'package_name' => 'Paket 50 Mbps',
            'speed' => '50 Mbps',
            'price' => 220000,
            'total' => 220000,
            'status' => 'Sedang Dipasang',
        ]);

        // Tagihan untuk pesanan yang masih "Sedang Dipasang"
        $pendingInstallationBill = Bill::create([
            'bill_number' => 'INV-202609-001',
            'order_id' => $inProgressOrder->id,
            'customer_name' => 'Rafi Razani In Progress',
            'customer_phone' => '081234567890',
            'customer_email' => 'rafi.progress@example.com',
            'address' => 'Jl. Pemasangan No. 12',
            'package_name' => 'Paket 50 Mbps',
            'speed' => '50 Mbps',
            'period' => '25 Sep 2026 – 25 Okt 2026',
            'due_date' => '05 Okt 2026',
            'bill_date' => '25 Sep 2026',
            'amount' => 220000,
            'tax' => 0,
            'total' => 220000,
            'status' => 'Belum Bayar',
        ]);

        // 2. Tagihan manual tanpa order (misal diinput langsung oleh admin)
        $manualBill = Bill::create([
            'bill_number' => 'INV-202609-MANUAL',
            'order_id' => null,
            'customer_name' => 'Pelanggan Tagihan Manual',
            'customer_phone' => '081299887755',
            'customer_email' => 'manual@example.com',
            'address' => 'Jl. Manual No. 5',
            'package_name' => 'Paket 30 Mbps',
            'speed' => '30 Mbps',
            'period' => '25 Sep 2026 – 25 Okt 2026',
            'due_date' => '05 Okt 2026',
            'bill_date' => '25 Sep 2026',
            'amount' => 165000,
            'tax' => 0,
            'total' => 165000,
            'status' => 'Belum Bayar',
        ]);

        // 3. Admin membuka halaman Monitoring Tagihan
        $response = $this->actingAs($admin)->get(route('admin.tagihan'));
        $response->assertOk();

        // Tagihan pemasangan yang BELUM selesai TIDAK boleh muncul
        $response->assertDontSee('INV-202609-001');
        $response->assertDontSee('Rafi Razani In Progress');

        // Tagihan manual tanpa order HARUS tetap muncul
        $response->assertSee('INV-202609-MANUAL');
        $response->assertSee('Pelanggan Tagihan Manual');

        // 4. Ubah status pesanan menjadi 'Selesai'
        $inProgressOrder->status = 'Selesai';
        $inProgressOrder->installed_at = now();
        $inProgressOrder->save();

        // 5. Admin refresh halaman Monitoring Tagihan
        $responseCompleted = $this->actingAs($admin)->get(route('admin.tagihan'));
        $responseCompleted->assertOk();

        // Sekarang tagihannya HARUS muncul di Monitoring Tagihan
        $responseCompleted->assertSee('INV-202609-001');
        $responseCompleted->assertSee('Rafi Razani In Progress');
    }

    public function test_sidebar_cek_badge_does_not_appear_when_monitoring_tagihan_is_empty(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        // Pesanan yang statusnya masih sedang dipasang / jadwal teknisi
        $inProgressOrder = Order::create([
            'order_number' => 'ORD-20260925-999',
            'customer_name' => 'Pelanggan Belum Selesai',
            'customer_phone' => '081234567899',
            'customer_email' => 'belumselesai@example.com',
            'address' => 'Jl. Belum Selesai No. 1',
            'package_name' => 'Paket 50 Mbps',
            'speed' => '50 Mbps',
            'price' => 220000,
            'total' => 220000,
            'status' => 'Sedang Dipasang',
        ]);

        // Tagihan terbit saat checkout awal, tapi pemasangan belum selesai
        Bill::create([
            'bill_number' => 'INV-202609-999',
            'order_id' => $inProgressOrder->id,
            'customer_name' => 'Pelanggan Belum Selesai',
            'customer_phone' => '081234567899',
            'customer_email' => 'belumselesai@example.com',
            'address' => 'Jl. Belum Selesai No. 1',
            'package_name' => 'Paket 50 Mbps',
            'speed' => '50 Mbps',
            'period' => '25 Sep 2026 – 25 Okt 2026',
            'due_date' => '05 Okt 2026',
            'bill_date' => '25 Sep 2026',
            'amount' => 220000,
            'tax' => 0,
            'total' => 220000,
            'status' => 'Belum Bayar',
        ]);

        // Kunjungi halaman monitoring tagihan
        $response = $this->actingAs($admin)->get(route('admin.tagihan'));
        $response->assertOk();

        // Badge 'Cek' di sidebar HARUS TIDAK MUNCUL karena monitoring tagihan kosong
        $response->assertDontSee('Cek');

        // Kunjungi juga halaman dashboard NOC
        $dashboardResponse = $this->actingAs($admin)->get(route('admin.dashboard'));
        $dashboardResponse->assertOk();
        $dashboardResponse->assertDontSee('Cek');

        // Setelah pesanan selesai
        $inProgressOrder->status = 'Selesai';
        $inProgressOrder->installed_at = now();
        $inProgressOrder->save();

        // Badge '1 Cek' sekarang harus muncul di sidebar
        $responseAfter = $this->actingAs($admin)->get(route('admin.tagihan'));
        $responseAfter->assertOk();
        $responseAfter->assertSee('1 Cek');
    }
}
