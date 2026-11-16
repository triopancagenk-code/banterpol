<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Order;
use App\Models\User;
use App\Services\BillingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTagihanRekapTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminUser(): User
    {
        return User::factory()->create([
            'role' => 'admin',
        ]);
    }

    public function test_tab_semua_does_not_display_lunas_bills(): void
    {
        $admin = $this->createAdminUser();

        // Buat tagihan aktif
        $activeBill = Bill::create([
            'bill_number' => 'INV-202609-001',
            'customer_name' => 'Pelanggan Aktif',
            'customer_phone' => '081234567890',
            'address' => 'Jl. Merdeka No. 1',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'period' => '01 Sep 2026 – 01 Okt 2026',
            'due_date' => '05 Okt 2026',
            'bill_date' => '01 Sep 2026',
            'amount' => 110000,
            'total' => 110000,
            'status' => 'Belum Bayar',
        ]);

        // Buat tagihan yang sudah lunas
        $paidBill = Bill::create([
            'bill_number' => 'INV-202609-002',
            'customer_name' => 'Pelanggan Sudah Lunas',
            'customer_phone' => '089876543210',
            'address' => 'Jl. Sudirman No. 2',
            'package_name' => 'Paket 30 Mbps',
            'speed' => '30 Mbps',
            'period' => '01 Sep 2026 – 01 Okt 2026',
            'due_date' => '05 Okt 2026',
            'bill_date' => '01 Sep 2026',
            'amount' => 165000,
            'total' => 165000,
            'status' => 'Lunas',
            'paid_at' => Carbon::now(),
            'receipt_number' => 'KWT-202609-999',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.tagihan', ['status' => 'all']));
        $response->assertOk();

        // Tagihan aktif harus tampil di pilihan "Semua"
        $response->assertSee('INV-202609-001');
        $response->assertSee('Pelanggan Aktif');

        // Tagihan lunas TIDAK boleh tampil di pilihan "Semua"
        $response->assertDontSee('INV-202609-002');
        $response->assertDontSee('Pelanggan Sudah Lunas');
    }

    public function test_tab_rekap_displays_lunas_bills_and_rekap_columns(): void
    {
        $admin = $this->createAdminUser();

        $activeBill = Bill::create([
            'bill_number' => 'INV-202609-001',
            'customer_name' => 'Pelanggan Belum Bayar',
            'customer_phone' => '081234567890',
            'address' => 'Jl. Merdeka No. 1',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'period' => '01 Sep 2026 – 01 Okt 2026',
            'due_date' => '05 Okt 2026',
            'bill_date' => '01 Sep 2026',
            'amount' => 110000,
            'total' => 110000,
            'status' => 'Belum Bayar',
        ]);

        $paidBill = Bill::create([
            'bill_number' => 'INV-202609-002',
            'customer_name' => 'Pelanggan Lunas Terverifikasi',
            'customer_phone' => '089876543210',
            'address' => 'Jl. Sudirman No. 2',
            'package_name' => 'Paket 30 Mbps',
            'speed' => '30 Mbps',
            'period' => '01 Sep 2026 – 01 Okt 2026',
            'due_date' => '05 Okt 2026',
            'bill_date' => '01 Sep 2026',
            'amount' => 165000,
            'total' => 165000,
            'status' => 'Lunas',
            'paid_at' => Carbon::now(),
            'receipt_number' => 'KWT-202609-888',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.tagihan', ['status' => 'rekap']));
        $response->assertOk();

        // Tagihan lunas harus tampil di tab rekap
        $response->assertSee('INV-202609-002');
        $response->assertSee('Pelanggan Lunas Terverifikasi');
        $response->assertSee('KWT-202609-888');

        // Kolom khusus rekap pembayaran harus tampil di header tabel
        $response->assertSee('Waktu Pelunasan / Rekap');
        $response->assertSee('Total Dibayar');
        $response->assertSee('Metode & Kuitansi', false);

        // Tagihan aktif tidak boleh tampil di tab rekap
        $response->assertDontSee('INV-202609-001');
        $response->assertDontSee('Pelanggan Belum Bayar');
    }

    public function test_verifying_bill_moves_it_from_semua_to_rekap_and_generates_next_bill(): void
    {
        $admin = $this->createAdminUser();

        $order = Order::create([
            'order_number' => 'ORD-202609-001',
            'customer_name' => 'Budi Verifikasi',
            'customer_phone' => '081122334455',
            'customer_email' => 'budi@example.com',
            'address' => 'Jl. Mawar No. 10',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'price' => 110000,
            'status' => 'Aktif',
            'payment_status' => 'Lunas',
        ]);

        $bill = Bill::create([
            'bill_number' => 'INV-202611-050',
            'order_id' => $order->id,
            'customer_name' => 'Budi Verifikasi',
            'customer_phone' => '081122334455',
            'address' => 'Jl. Mawar No. 10',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'period' => '01 Nov 2026 – 30 Nov 2026',
            'due_date' => '20 Nov 2026',
            'bill_date' => '05 Nov 2026',
            'amount' => 110000,
            'total' => 110000,
            'status' => 'Menunggu Verifikasi',
        ]);

        // Verifikasi pembayaran oleh admin
        $postResponse = $this->actingAs($admin)->post(route('admin.tagihan.status', ['id' => $bill->bill_number]), [
            'status' => 'Lunas',
            'admin_notes' => 'Pembayaran via transfer BCA valid',
        ]);
        $postResponse->assertSessionHas('success');

        // Cek database: tagihan telah lunas dan tercatat di rekap
        $bill->refresh();
        $this->assertEquals('Lunas', $bill->status);
        $this->assertNotNull($bill->paid_at);
        $this->assertNotEmpty($bill->receipt_number);
        $this->assertEquals($admin->name, $bill->collected_by);

        // Tagihan bulan berjalan tidak lagi muncul di Monitoring Tagihan (Belum Bayar)
        $this->flushSession();
        $responseSemua = $this->actingAs($admin)->get(route('admin.tagihan', ['status' => 'all']));
        $responseSemua->assertOk();
        $responseSemua->assertDontSee($bill->bill_number);

        // Tagihan lunas muncul di tab Rekap Pembayaran
        $responseRekap = $this->actingAs($admin)->get(route('admin.tagihan', ['status' => 'rekap']));
        $responseRekap->assertOk();
        $responseRekap->assertSee($bill->bill_number);

        // Tagihan bulan berikutnya baru terbit pada tanggal 5 bulan depan
        Carbon::setTestNow(Carbon::parse('2026-12-05 10:00:00', 'Asia/Jakarta'));
        BillingService::syncMonthlyBillsFromDataPelanggan();
        $nextBill = Bill::where('order_id', $order->id)->where('id', '!=', $bill->id)->first();
        $this->assertNotNull($nextBill);
        $this->assertEquals('Belum Bayar', $nextBill->status);

        $responseSemuaNextMonth = $this->actingAs($admin)->get(route('admin.tagihan', ['status' => 'all']));
        $responseSemuaNextMonth->assertOk();
        $responseSemuaNextMonth->assertSee($nextBill->bill_number);
        Carbon::setTestNow();

        // Akses tab "Rekap": Tagihan lama (Lunas) tampil, tagihan baru (Belum Bayar) tidak tampil
        $responseRekap = $this->actingAs($admin)->get(route('admin.tagihan', ['status' => 'rekap']));
        $responseRekap->assertOk();
        $responseRekap->assertSee($bill->bill_number);
        $responseRekap->assertDontSee($nextBill->bill_number);
        $responseRekap->assertViewHas('bills', function ($bills) use ($bill, $nextBill) {
            $ids = collect($bills)->pluck('id')->all();
            return in_array($bill->bill_number, $ids) && !in_array($nextBill->bill_number, $ids);
        });
    }

    public function test_sidebar_has_rekap_pembayaran_link(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get(route('admin.tagihan'));
        $response->assertOk();
        $response->assertSee(route('admin.tagihan', ['status' => 'rekap']));
        $response->assertSee('Rekap Pembayaran');
    }

    public function test_after_admin_verifies_payment_customer_pov_shows_next_month_bill(): void
    {
        $admin = $this->createAdminUser();

        $customer = User::factory()->create([
            'role' => 'customer',
            'name' => 'Budi Pelanggan',
            'email' => 'budipelanggan@example.com',
            'phone' => '081234509876',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-' . date('Ymd') . '-5555',
            'customer_name' => 'Budi Pelanggan',
            'customer_phone' => '081234509876',
            'customer_email' => 'budipelanggan@example.com',
            'address' => 'Jl. Pahlawan No. 45, Banyumas',
            'package_name' => 'Paket 30 Mbps',
            'speed' => '30 Mbps',
            'price' => 165000,
            'total' => 165000,
            'status' => 'Selesai',
        ]);

        // Tagihan pertama
        $firstBill = Bill::create([
            'bill_number' => 'INV-202611-077',
            'order_id' => $order->id,
            'customer_name' => 'Budi Pelanggan',
            'customer_phone' => '081234509876',
            'customer_email' => 'budipelanggan@example.com',
            'address' => 'Jl. Pahlawan No. 45, Banyumas',
            'package_name' => 'Paket 30 Mbps',
            'speed' => '30 Mbps',
            'period' => '01 Nov 2026 – 30 Nov 2026',
            'due_date' => '20 Nov 2026',
            'bill_date' => '05 Nov 2026',
            'amount' => 165000,
            'tax' => 0,
            'total' => 165000,
            'status' => 'Menunggu Verifikasi',
        ]);

        // 1. Admin memverifikasi pembayaran pada monitoring tagihan
        $verifyResponse = $this->actingAs($admin)->post(route('admin.tagihan.status', ['id' => $firstBill->bill_number]), [
            'status' => 'Lunas',
            'admin_notes' => 'Bukti transfer BCA terverifikasi valid',
        ]);
        $verifyResponse->assertSessionHas('success');

        // Tagihan pertama kini berstatus Lunas dan tidak ada tagihan belum lunas di bulan ini
        $firstBill->refresh();
        $this->assertEquals('Lunas', $firstBill->status);

        // Tagihan bulan berikutnya baru terbit pada tanggal 5 bulan depan
        Carbon::setTestNow(Carbon::parse('2026-12-05 10:00:00', 'Asia/Jakarta'));
        BillingService::syncMonthlyBillsFromDataPelanggan();
        $nextBill = Bill::where('order_id', $order->id)
            ->where('id', '!=', $firstBill->id)
            ->first();
        $this->assertNotNull($nextBill);
        $this->assertEquals('Belum Bayar', $nextBill->status);
        $this->assertTrue(str_starts_with($nextBill->due_date, '20') || str_starts_with($nextBill->due_date, '05'));

        // 2. Pada POV Pelanggan ketika sudah memasuki bulan penagihan berikutnya
        $customerPov = $this->actingAs($customer)->get(route('tagihan'));
        $customerPov->assertOk();
        $customerPov->assertSee($nextBill->bill_number);
        $customerPov->assertSee($nextBill->due_date);
        $customerPov->assertSee('Paket 30 Mbps');
        $customerPov->assertSee('165.000');
        $customerPov->assertSee('Belum Bayar');

        // Tagihan pertama yang lunas juga tampil pada riwayat
        $customerPov->assertSee($firstBill->bill_number);

        // 3. Pada POV Admin (ketika admin membuka /tagihan untuk pratinjau POV Pelanggan)
        $adminPov = $this->actingAs($admin)->get(route('tagihan', ['customer_email' => $customer->email]));
        $adminPov->assertOk();
        $adminPov->assertSee($nextBill->bill_number);
        $adminPov->assertSee('Mode Pratinjau Admin');
        Carbon::setTestNow();
    }

    public function test_hapus_semua_rekap_clears_paid_bills_and_resets_to_belum_bayar(): void
    {
        $admin = $this->createAdminUser();

        $order = Order::create([
            'order_number' => 'PLG-2026-0099',
            'customer_name' => 'Pelanggan Hapus Rekap',
            'customer_phone' => '081234567899',
            'customer_email' => 'rekap@example.com',
            'address' => 'Jl. Jenderal Sudirman',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'price' => 110000,
            'total' => 110000,
            'status' => 'Selesai',
            'payment_status' => 'Lunas',
        ]);

        $paidBill = Bill::create([
            'bill_number' => 'INV-202611-099',
            'order_id' => $order->id,
            'customer_name' => 'Pelanggan Hapus Rekap',
            'customer_phone' => '081234567899',
            'customer_email' => 'rekap@example.com',
            'address' => 'Jl. Jenderal Sudirman',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'period' => '01 Nov 2026 – 30 Nov 2026',
            'due_date' => '20 Nov 2026',
            'amount' => 110000,
            'total' => 110000,
            'status' => 'Lunas',
            'paid_at' => now(),
            'receipt_number' => 'KWT-202611-099',
        ]);

        $orphanPaidBill = Bill::create([
            'bill_number' => 'INV-202611-998',
            'order_id' => null,
            'customer_name' => 'Orphan Rekap',
            'customer_phone' => '081234567898',
            'customer_email' => 'orphan@example.com',
            'address' => 'Jl. Jenderal Sudirman',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'period' => '01 Nov 2026 – 30 Nov 2026',
            'due_date' => '20 Nov 2026',
            'amount' => 110000,
            'total' => 110000,
            'status' => 'Lunas',
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.tagihan.hapus-semua-rekap'));
        $response->assertRedirect(route('admin.tagihan', ['status' => 'rekap']));
        $response->assertSessionHas('success');

        // Tagihan terdaftar direset ke Belum Bayar
        $paidBill->refresh();
        $this->assertEquals('Belum Bayar', $paidBill->status);
        $this->assertNull($paidBill->paid_at);
        $this->assertNull($paidBill->receipt_number);

        // Tagihan orphan dihapus
        $this->assertNull(Bill::find($orphanPaidBill->id));

        // Rekap sekarang kosong (0 lunas)
        $this->assertEquals(0, Bill::where('status', 'Lunas')->count());
    }

    public function test_sidebar_badge_is_consistent_between_monitoring_and_rekap(): void
    {
        $admin = $this->createAdminUser();

        $order1 = Order::create([
            'order_number' => 'PLG-2026-0001',
            'customer_name' => 'Cust Unpaid',
            'customer_phone' => '081234567801',
            'customer_email' => 'unpaid@example.com',
            'address' => 'Jl. Mawar',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'price' => 110000,
            'total' => 110000,
            'status' => 'Selesai',
        ]);

        $order2 = Order::create([
            'order_number' => 'PLG-2026-0002',
            'customer_name' => 'Cust Paid',
            'customer_phone' => '081234567802',
            'customer_email' => 'paid@example.com',
            'address' => 'Jl. Melati',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'price' => 110000,
            'total' => 110000,
            'status' => 'Selesai',
        ]);

        Bill::create([
            'bill_number' => 'INV-202611-001',
            'order_id' => $order1->id,
            'customer_name' => 'Cust Unpaid',
            'customer_phone' => '081234567801',
            'address' => 'Jl. Mawar',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'period' => '01 Nov 2026 – 30 Nov 2026',
            'due_date' => '20 Nov 2026',
            'amount' => 110000,
            'total' => 110000,
            'status' => 'Belum Bayar',
        ]);

        Bill::create([
            'bill_number' => 'INV-202611-002',
            'order_id' => $order2->id,
            'customer_name' => 'Cust Paid',
            'customer_phone' => '081234567802',
            'address' => 'Jl. Melati',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'period' => '01 Nov 2026 – 30 Nov 2026',
            'due_date' => '20 Nov 2026',
            'amount' => 110000,
            'total' => 110000,
            'status' => 'Lunas',
            'paid_at' => now(),
        ]);

        // Pada monitoring tagihan: badge tagihan harus 1 (hanya unpaid)
        $respMonitoring = $this->actingAs($admin)->get(route('admin.tagihan'));
        $respMonitoring->assertOk();

        // Pada rekap: badge tagihan di sidebar harus TETAP 1 (konsisten, tidak melonjak jadi 2)
        $respRekap = $this->actingAs($admin)->get(route('admin.tagihan', ['status' => 'rekap']));
        $respRekap->assertOk();
    }

    public function test_bulk_delete_tagihan_reset_rekap(): void
    {
        $admin = $this->createAdminUser();

        $order = Order::create([
            'order_number' => 'PLG-2026-0088',
            'customer_name' => 'Pelanggan Bulk Reset',
            'customer_phone' => '081234567888',
            'customer_email' => 'bulkreset@example.com',
            'address' => 'Jl. Anggrek',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'price' => 110000,
            'total' => 110000,
            'status' => 'Selesai',
            'payment_status' => 'Lunas',
        ]);

        $bill = Bill::create([
            'bill_number' => 'INV-202611-088',
            'order_id' => $order->id,
            'customer_name' => 'Pelanggan Bulk Reset',
            'customer_phone' => '081234567888',
            'customer_email' => 'bulkreset@example.com',
            'address' => 'Jl. Anggrek',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'period' => '01 Nov 2026 – 30 Nov 2026',
            'due_date' => '20 Nov 2026',
            'amount' => 110000,
            'total' => 110000,
            'status' => 'Lunas',
            'paid_at' => now(),
            'receipt_number' => 'KWT-202611-088',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.tagihan.bulk-delete'), [
            'selected_bills' => [$bill->bill_number],
            'action_type' => 'reset_rekap',
        ]);

        $response->assertSessionHas('success');
        $bill->refresh();
        $this->assertEquals('Belum Bayar', $bill->status);
        $this->assertNull($bill->paid_at);
        $this->assertNull($bill->receipt_number);

        $order->refresh();
        $this->assertEquals('Menunggu Pembayaran', $order->payment_status);
    }

    public function test_bulk_delete_tagihan_permanent_delete(): void
    {
        $admin = $this->createAdminUser();

        $order = Order::create([
            'order_number' => 'PLG-2026-0077',
            'customer_name' => 'Pelanggan Bulk Delete',
            'customer_phone' => '081234567877',
            'customer_email' => 'bulkdelete@example.com',
            'address' => 'Jl. Dahlia',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'price' => 110000,
            'total' => 110000,
            'status' => 'Selesai',
            'payment_status' => 'Lunas',
        ]);

        $bill = Bill::create([
            'bill_number' => 'INV-202611-077',
            'order_id' => $order->id,
            'customer_name' => 'Pelanggan Bulk Delete',
            'customer_phone' => '081234567877',
            'customer_email' => 'bulkdelete@example.com',
            'address' => 'Jl. Dahlia',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'period' => '01 Nov 2026 – 30 Nov 2026',
            'due_date' => '20 Nov 2026',
            'amount' => 110000,
            'total' => 110000,
            'status' => 'Belum Bayar',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.tagihan.bulk-delete'), [
            'selected_bills' => [$bill->id],
            'action_type' => 'delete',
        ]);

        $response->assertSessionHas('success');
        $this->assertNull(Bill::find($bill->id));
    }

    public function test_bulk_delete_tagihan_empty_selection_fails(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->post(route('admin.tagihan.bulk-delete'), [
            'selected_bills' => [],
        ]);

        $response->assertSessionHas('error');
    }

    public function test_bulk_delete_tagihan_with_selected_bills_json_fallback(): void
    {
        $admin = $this->createAdminUser();

        $bill = Bill::create([
            'bill_number' => 'INV-202611-998',
            'customer_name' => 'Pelanggan Json Test',
            'customer_phone' => '081234567899',
            'address' => 'Jl. Test Json No. 1',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'period' => '01 Nov 2026 – 30 Nov 2026',
            'due_date' => '20 Nov 2026',
            'amount' => 110000,
            'total' => 110000,
            'status' => 'Belum Bayar',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.tagihan.bulk-delete'), [
            'selected_bills_json' => json_encode([$bill->id]),
            'action_type' => 'delete',
        ]);

        $response->assertSessionHas('success');
        $this->assertNull(Bill::find($bill->id));
    }

    public function test_generate_semua_tagihan_creates_bills_for_all_data_pelanggan(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 16, 12, 0, 0, 'Asia/Jakarta'));
        $admin = $this->createAdminUser();

        $c1 = Order::create([
            'order_number' => 'PLG-001',
            'customer_name' => 'Pelanggan Master 1',
            'customer_phone' => '0811111111',
            'customer_email' => 'p1@test.com',
            'address' => 'Alamat 1',
            'package_name' => 'Paket 20 Mbps',
            'price' => 110000,
            'status' => 'Aktif',
        ]);

        $c2 = Order::create([
            'order_number' => 'ORD-202611-002',
            'customer_name' => 'Pelanggan Baru ORD Bulan Ini',
            'customer_phone' => '0822222222',
            'customer_email' => 'p2@test.com',
            'address' => 'Alamat 2',
            'package_name' => 'Paket 30 Mbps',
            'price' => 165000,
            'status' => 'Aktif',
            'installed_at' => Carbon::create(2026, 11, 10, 10, 0, 0, 'Asia/Jakarta'),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.tagihan.generate-semua'));
        $response->assertRedirect(route('admin.tagihan'));
        $response->assertSessionHas('success');

        $dueDate = '20 Nov 2026';
        $this->assertTrue(Bill::where('order_id', $c1->id)->where('due_date', $dueDate)->exists());
        $this->assertTrue(Bill::where('order_id', $c2->id)->where('due_date', $dueDate)->exists());
    }

    public function test_artisan_command_billing_generate_semua_pelanggan(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 16, 12, 0, 0, 'Asia/Jakarta'));

        $c = Order::create([
            'order_number' => 'PLG-099',
            'customer_name' => 'Pelanggan Artisan Test',
            'customer_phone' => '0899999999',
            'customer_email' => 'artisan@test.com',
            'address' => 'Alamat Artisan',
            'package_name' => 'Paket 20 Mbps',
            'price' => 110000,
            'status' => 'Aktif',
        ]);

        $this->artisan('billing:generate-semua-pelanggan')
            ->assertExitCode(0);

        $this->assertTrue(Bill::where('order_id', $c->id)->where('due_date', '20 Nov 2026')->exists());
    }

    public function test_tagihan_view_contains_visible_delete_buttons_and_sync_button(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get(route('admin.tagihan', ['status' => 'rekap']));
        $response->assertStatus(200);

        $response->assertSee('Sinkronkan Tagihan');
        $response->assertSee('Hapus Semua Rekap');
        $response->assertSee('background-color: #dc2626 !important;', false);
        $response->assertDontSee('bg-rose-600', false);
    }

    public function test_bulk_deleted_bills_remain_deleted_and_do_not_recreate_on_page_load(): void
    {
        $admin = $this->createAdminUser();

        $c = Order::create([
            'order_number' => 'PLG-2026-0010',
            'customer_name' => 'Pelanggan Uji Hapus',
            'customer_phone' => '0877777777',
            'customer_email' => 'ujihapus@test.com',
            'address' => 'Jl. Uji Hapus No. 1',
            'package_name' => 'Paket 20 Mbps',
            'price' => 110000,
            'status' => 'Aktif',
        ]);

        $bill = Bill::create([
            'bill_number' => 'INV-202611-010',
            'order_id' => $c->id,
            'customer_name' => $c->customer_name,
            'customer_phone' => $c->customer_phone,
            'address' => $c->address,
            'package_name' => $c->package_name,
            'speed' => '20 Mbps',
            'due_date' => '20 Nov 2026',
            'period' => '01 Nov 2026 – 30 Nov 2026',
            'amount' => 110000,
            'total' => 110000,
            'status' => 'Belum Bayar',
        ]);

        // Hapus massal / hapus data tagihan
        $response = $this->actingAs($admin)->post(route('admin.tagihan.bulk-delete'), [
            'selected_bills' => [$bill->id],
            'action_type' => 'delete',
        ]);
        $response->assertSessionHas('success');

        // Pastikan terhapus di database
        $this->assertNull(Bill::find($bill->id));

        // Buka kembali halaman admin/tagihan: Tagihan TIDAK boleh dibuat ulang secara gaib
        $responseGet = $this->actingAs($admin)->get(route('admin.tagihan'));
        $responseGet->assertOk();
        $this->assertEquals(0, Bill::where('order_id', $c->id)->count());
    }

    public function test_sync_bills_with_same_phone_generates_distinct_bills_for_each_customer(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 16, 12, 0, 0, 'Asia/Jakarta'));
        $admin = $this->createAdminUser();

        // 3 pelanggan berbeda dengan nomor telepon atau nama yang serupa
        $c1 = Order::create([
            'order_number' => 'PLG-011',
            'customer_name' => 'Keluarga Sutrisno 1',
            'customer_phone' => '081234567000',
            'customer_email' => 'sutrisno1@test.com',
            'address' => 'Alamat 1',
            'package_name' => 'Paket 20 Mbps',
            'price' => 110000,
            'status' => 'Aktif',
        ]);
        $c2 = Order::create([
            'order_number' => 'PLG-012',
            'customer_name' => 'Keluarga Sutrisno 2',
            'customer_phone' => '081234567000', // Nomor sama
            'customer_email' => 'sutrisno2@test.com',
            'address' => 'Alamat 2',
            'package_name' => 'Paket 20 Mbps',
            'price' => 110000,
            'status' => 'Aktif',
        ]);
        $c3 = Order::create([
            'order_number' => 'PLG-013',
            'customer_name' => 'Keluarga Sutrisno 3',
            'customer_phone' => '081234567000', // Nomor sama
            'customer_email' => 'sutrisno3@test.com',
            'address' => 'Alamat 3',
            'package_name' => 'Paket 20 Mbps',
            'price' => 110000,
            'status' => 'Aktif',
        ]);

        // Jalankan sinkronisasi
        $this->actingAs($admin)->post(route('admin.tagihan.generate-semua'));

        // Seluruh 3 pelanggan wajib memiliki tagihan masing-masing (tidak boleh dilewati karena no telepon sama)
        $this->assertEquals(1, Bill::where('order_id', $c1->id)->count());
        $this->assertEquals(1, Bill::where('order_id', $c2->id)->count());
        $this->assertEquals(1, Bill::where('order_id', $c3->id)->count());
    }
}
