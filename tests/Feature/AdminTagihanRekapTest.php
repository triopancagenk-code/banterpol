<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Order;
use App\Models\User;
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
            'bill_number' => 'INV-202609-050',
            'order_id' => $order->id,
            'customer_name' => 'Budi Verifikasi',
            'customer_phone' => '081122334455',
            'address' => 'Jl. Mawar No. 10',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'period' => '01 Sep 2026 – 01 Okt 2026',
            'due_date' => '05 Okt 2026',
            'bill_date' => '01 Sep 2026',
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

        // Cek database
        $bill->refresh();
        $this->assertEquals('Lunas', $bill->status);
        $this->assertNotNull($bill->paid_at);
        $this->assertNotEmpty($bill->receipt_number);
        $this->assertEquals($admin->name, $bill->collected_by);

        // Tagihan bulan berikutnya otomatis dibuat karena sudah bayar
        $nextBill = Bill::where('order_id', $order->id)->where('bill_number', '!=', $bill->bill_number)->first();
        $this->assertNotNull($nextBill);
        $this->assertEquals('Belum Bayar', $nextBill->status);

        // Flush session banner agar tidak mencocokkan pesan flash notification
        $this->flushSession();

        // Akses tab "Semua": Tagihan lama (Lunas) tidak tampil di daftar tagihan aktif, tagihan baru (Belum Bayar) tampil
        $responseSemua = $this->actingAs($admin)->get(route('admin.tagihan', ['status' => 'all']));
        $responseSemua->assertOk();
        $responseSemua->assertSee($nextBill->bill_number);
        $responseSemua->assertViewHas('bills', function ($bills) use ($bill, $nextBill) {
            $ids = collect($bills)->pluck('id')->all();
            return !in_array($bill->bill_number, $ids) && in_array($nextBill->bill_number, $ids);
        });

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
            'bill_number' => 'INV-202609-077',
            'order_id' => $order->id,
            'customer_name' => 'Budi Pelanggan',
            'customer_phone' => '081234509876',
            'customer_email' => 'budipelanggan@example.com',
            'address' => 'Jl. Pahlawan No. 45, Banyumas',
            'package_name' => 'Paket 30 Mbps',
            'speed' => '30 Mbps',
            'period' => '01 Sep 2026 – 01 Okt 2026',
            'due_date' => '05 Okt 2026',
            'bill_date' => '01 Sep 2026',
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

        // Tagihan pertama kini berstatus Lunas
        $firstBill->refresh();
        $this->assertEquals('Lunas', $firstBill->status);

        // Tagihan bulan selanjutnya otomatis dibuat dengan jatuh tempo tanggal 05
        $nextBill = Bill::where('order_id', $order->id)
            ->where('id', '!=', $firstBill->id)
            ->first();
        $this->assertNotNull($nextBill);
        $this->assertEquals('Belum Bayar', $nextBill->status);
        $this->assertStringStartsWith('05', $nextBill->due_date);

        // 2. Pada POV Pelanggan (ketika pelanggan login dan buka halaman /tagihan)
        $customerPov = $this->actingAs($customer)->get(route('tagihan'));
        $customerPov->assertOk();

        // Tagihan bulan selanjutnya harus muncul di POV pelanggan!
        $customerPov->assertSee($nextBill->bill_number);
        $customerPov->assertSee('Total Tagihan Belum Dibayar');
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
    }
}
