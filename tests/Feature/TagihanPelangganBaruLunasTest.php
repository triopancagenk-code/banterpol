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

        // Akses halaman tagihan sebagai pelanggan
        $response = $this->actingAs($user)->get(route('tagihan'));

        $response->assertStatus(200);
        // Pastikan tidak ada tagihan belum dibayar
        $response->assertDontSee('Total Tagihan Belum Dibayar');
        $response->assertDontSee('1 tagihan belum dibayar');
        // Pastikan informasi lunas pemasangan muncul dan tagihan akan terbit bulan depan
        $response->assertSee('Pembayaran Pemasangan Lunas');
        $response->assertSee('Tidak Ada Tagihan Berjalan');
        $response->assertSee('Tagihan berikutnya akan muncul untuk bulan');
    }

    public function test_pelanggan_baru_unpaid_installation_creates_bill_and_cleans_up_when_marked_lunas(): void
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

        // Tagihan terbit karena pemasangan belum lunas
        $bill = BillingService::generateBillForOrder($order);
        $this->assertNotNull($bill);
        $this->assertEquals('Belum Bayar', $bill->status);

        // Admin kemudian mengonfirmasi pembayaran pemasangan menjadi 'Lunas'
        $updateResponse = $this->actingAs($admin)->post(route('admin.pesanan.status', ['id' => $order->id]), [
            'status' => 'Selesai',
            'payment_status' => 'Lunas',
        ]);
        $updateResponse->assertSessionHas('success');

        // Tagihan unpaid untuk bulan pemasangan harus otomatis terhapus
        $remainingBills = Bill::where('order_id', $order->id)->whereIn('status', ['Belum Bayar', 'Jatuh Tempo'])->get();
        $this->assertCount(0, $remainingBills);

        // Di halaman tagihan pelanggan juga tidak muncul tagihan belum dibayar
        $response = $this->actingAs($user)->get(route('tagihan'));
        $response->assertStatus(200);
        $response->assertDontSee('Total Tagihan Belum Dibayar');
        $response->assertSee('Tidak Ada Tagihan Berjalan');
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
