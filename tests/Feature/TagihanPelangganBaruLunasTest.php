<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Order;
use App\Models\User;
use App\Services\BillingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagihanPelangganBaruLunasTest extends TestCase
{
    use RefreshDatabase;

    private function createCustomerUser(string $name = 'Pelanggan Root', string $email = 'root@gmail.com', string $phone = '081234567891'): User
    {
        return User::factory()->create([
            'role' => 'customer',
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
        ]);
    }

    private function createAdminUser(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@banterpol.net',
        ]);
    }

    public function test_pelanggan_baru_with_lunas_installation_has_no_bill_for_installation_month(): void
    {
        $admin = $this->createAdminUser();
        $user = $this->createCustomerUser();

        // Buat pesanan baru yang sudah terpasang dan status pembayaran pemasangan Lunas di bulan berjalan
        $order = Order::create([
            'order_number' => 'ORD-20261113-163',
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_phone' => $user->phone,
            'customer_email' => $user->email,
            'address' => 'Jl. Lesmana No. 10, Banyumas',
            'package_name' => 'Paket 30 Mbps',
            'speed' => '30 Mbps',
            'price' => 165000,
            'installation_fee' => 0,
            'tax' => 0,
            'total' => 165000,
            'status' => 'Selesai',
            'payment_status' => 'Lunas',
            'payment_method' => 'Bayar Setelah Pemasangan (Konfirmasi Admin)',
            'installation_date' => now('Asia/Jakarta')->toDateString(),
            'installed_at' => now('Asia/Jakarta'),
        ]);

        // Coba jalankan generateBillForOrder & syncMonthlyBillsFromDataPelanggan
        BillingService::generateBillForOrder($order);
        BillingService::syncMonthlyBillsFromDataPelanggan();

        // Pastikan TIDAK ADA tagihan yang dibuat untuk bulan pemasangan ini
        $bills = Bill::where('order_id', $order->id)->get();
        $this->assertCount(0, $bills);

        // Muncul di Data Pelanggan
        $responsePelanggan = $this->actingAs($admin)->get(route('admin.pelanggan'));
        $responsePelanggan->assertOk();
        $responsePelanggan->assertSee($user->name);

        // Tidak muncul di Monitoring Tagihan bulan ini (mengikuti Data Pelanggan lama, bukan Monitoring Pesanan)
        $responseTagihan = $this->actingAs($admin)->get(route('admin.tagihan'));
        $responseTagihan->assertOk();
        $responseTagihan->assertViewHas('bills', function ($bills) use ($order) {
            return !collect($bills)->pluck('order_id')->contains($order->id);
        });
    }

    public function test_pelanggan_baru_unpaid_installation_creates_no_bill_for_current_month(): void
    {
        $admin = $this->createAdminUser();
        $user = $this->createCustomerUser('Pelanggan Belum Bayar', 'belumbayar@gmail.com', '081234567892');

        // Buat pesanan baru selesai tetapi belum bayar pemasangan
        $order = Order::create([
            'order_number' => 'ORD-20261113-164',
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_phone' => $user->phone,
            'customer_email' => $user->email,
            'address' => 'Jl. Lesmana No. 12, Banyumas',
            'package_name' => 'Paket 30 Mbps',
            'speed' => '30 Mbps',
            'price' => 165000,
            'installation_fee' => 0,
            'tax' => 0,
            'total' => 165000,
            'status' => 'Selesai',
            'payment_status' => 'Menunggu Pembayaran',
            'payment_method' => 'Bayar Setelah Pemasangan (Konfirmasi Admin)',
            'installation_date' => now('Asia/Jakarta')->toDateString(),
            'installed_at' => now('Asia/Jakarta'),
        ]);

        // Tagihan bulan berjalan tidak terbit untuk pelanggan baru yang baru memesan bulan ini
        $bill = BillingService::generateBillForOrder($order);
        $this->assertNull($bill);

        // Pelanggan muncul di Data Pelanggan
        $responsePelanggan = $this->actingAs($admin)->get(route('admin.pelanggan'));
        $responsePelanggan->assertOk();
        $responsePelanggan->assertSee($order->customer_name);

        // Tagihan tidak muncul di monitoring tagihan bulan berjalan
        $responseTagihan = $this->actingAs($admin)->get(route('admin.tagihan'));
        $responseTagihan->assertOk();
        $responseTagihan->assertViewHas('bills', function ($bills) use ($order) {
            return !collect($bills)->pluck('order_id')->contains($order->id);
        });
    }

    public function test_pelanggan_baru_with_lunas_installation_gets_bill_in_next_month(): void
    {
        $user = $this->createCustomerUser('Pelanggan Bulan Lalu', 'bulanlalu@gmail.com', '081234567893');

        // Pelanggan dipasang bulan Oktober 2026 dan pembayaran pemasangan Lunas
        $order = Order::create([
            'order_number' => 'ORD-20261015-100',
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_phone' => $user->phone,
            'customer_email' => $user->email,
            'address' => 'Jl. Desa Karanganyar, Banyumas',
            'package_name' => 'Paket 30 Mbps',
            'speed' => '30 Mbps',
            'price' => 165000,
            'installation_fee' => 0,
            'tax' => 0,
            'total' => 165000,
            'status' => 'Selesai',
            'payment_status' => 'Lunas',
            'payment_method' => 'Bayar Setelah Pemasangan (Konfirmasi Admin)',
            'installation_date' => '2026-10-15',
            'installed_at' => Carbon::parse('2026-10-15 10:00:00'),
            'created_at' => Carbon::parse('2026-10-15 09:00:00'),
        ]);

        // Simulasikan waktu saat ini adalah bulan selanjutnya: 05 November 2026
        $nowNovember = Carbon::parse('2026-11-05 10:00:00', 'Asia/Jakarta');
        BillingService::syncMonthlyBillsFromDataPelanggan($nowNovember);

        // Tagihan untuk bulan selanjutnya (November) harus terbit!
        $novemberBill = Bill::where('order_id', $order->id)->first();
        $this->assertNotNull($novemberBill);
        $this->assertStringContainsString('Nov 2026', $novemberBill->period);
        $this->assertStringContainsString('20 Nov 2026', $novemberBill->due_date);
        $this->assertEquals('Belum Bayar', $novemberBill->status);
        $this->assertEquals(165000, (float)$novemberBill->total);
    }
}
