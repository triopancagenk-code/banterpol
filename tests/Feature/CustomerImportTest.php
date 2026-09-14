<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CustomerImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin@banterpool.net',
            'role' => 'admin',
        ]);

        $this->customer = User::factory()->create([
            'email' => 'customer@example.com',
            'role' => 'customer',
        ]);
    }

    public function test_guest_cannot_access_import_or_template(): void
    {
        $response = $this->get(route('admin.pelanggan.template'));
        $response->assertRedirect(route('login'));

        $response = $this->post(route('admin.pelanggan.import'), []);
        $response->assertRedirect(route('login'));
    }

    public function test_customer_cannot_access_import_or_template(): void
    {
        $response = $this->actingAs($this->customer)->get(route('admin.pelanggan.template'));
        $response->assertStatus(403);

        $response = $this->actingAs($this->customer)->post(route('admin.pelanggan.import'), []);
        $response->assertStatus(403);
    }

    public function test_admin_can_download_excel_template(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.pelanggan.template'));
        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename=Template_Import_Pelanggan_Banterpool.xls');
    }

    public function test_admin_can_download_csv_template(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.pelanggan.template', ['format' => 'csv']));
        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename=Template_Import_Pelanggan_Banterpool.csv');
    }

    public function test_admin_can_import_customers_from_csv(): void
    {
        $csvContent = "No. Pelanggan,No. KTP / NIK,Nama Lengkap Pelanggan,Tempat Lahir,Tanggal Lahir,No. Handphone (WA),Alamat Email,Jenis Layanan (Paket),Kecepatan,Harga / Bulan (Rp),Alamat Lengkap Pemasangan,Status Berlangganan\n"
            . "PLG-2026-9001,3302190101900001,Budi Hermawan,Banyumas,1990-01-01,081234567890,budi@example.com,Paket 20 Mbps,20 Mbps,110000,Batuanten RT 01/RW 02 Kec. Cilongok,Selesai\n"
            . "PLG-2026-9002,3302190202920002,Sari Dewi,Banyumas,1992-02-02,085712345678,sari@example.com,Paket 30 Mbps,30 Mbps,165000,Panusupan RT 02/RW 03 Kec. Cilongok,Selesai\n";

        $file = UploadedFile::fake()->createWithContent('pelanggan_baru.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(route('admin.pelanggan.import'), [
            'file' => $file,
            'update_existing' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('orders', [
            'order_number' => 'PLG-2026-9001',
            'customer_name' => 'Budi Hermawan',
            'id_card_number' => '3302190101900001',
            'package_name' => 'Paket 20 Mbps',
            'price' => 110000,
        ]);

        $this->assertDatabaseHas('orders', [
            'order_number' => 'PLG-2026-9002',
            'customer_name' => 'Sari Dewi',
            'id_card_number' => '3302190202920002',
            'package_name' => 'Paket 30 Mbps',
            'price' => 165000,
        ]);
    }

    public function test_admin_can_update_existing_customer_via_import(): void
    {
        $existingOrder = Order::create([
            'order_number' => 'PLG-2026-8001',
            'customer_name' => 'Nama Lama',
            'id_card_number' => '3302190909990009',
            'customer_phone' => '0811111111',
            'customer_email' => 'lama@example.com',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'price' => 110000,
            'total' => 110000,
            'address' => 'Alamat Lama',
            'status' => 'Menunggu Konfirmasi',
            'payment_status' => 'Lunas',
        ]);

        $csvContent = "No. Pelanggan,No. KTP / NIK,Nama Lengkap Pelanggan,Tempat Lahir,Tanggal Lahir,No. Handphone (WA),Alamat Email,Jenis Layanan (Paket),Kecepatan,Harga / Bulan (Rp),Alamat Lengkap Pemasangan,Status Berlangganan\n"
            . "PLG-2026-8001,3302190909990009,Nama Baru Terupdate,Banyumas,1999-09-09,0822222222,baru@example.com,Paket 50 Mbps,50 Mbps,220000,Alamat Baru Cilongok,Selesai\n";

        $file = UploadedFile::fake()->createWithContent('update_pelanggan.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(route('admin.pelanggan.import'), [
            'file' => $file,
            'update_existing' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('orders', [
            'id' => $existingOrder->id,
            'order_number' => 'PLG-2026-8001',
            'customer_name' => 'Nama Baru Terupdate',
            'package_name' => 'Paket 50 Mbps',
            'price' => 220000,
            'status' => 'Selesai',
        ]);
    }

    public function test_import_skips_invalid_rows_and_shows_warnings(): void
    {
        $csvContent = "No. Pelanggan,No. KTP / NIK,Nama Lengkap Pelanggan,Tempat Lahir,Tanggal Lahir,No. Handphone (WA),Alamat Email,Jenis Layanan (Paket),Kecepatan,Harga / Bulan (Rp),Alamat Lengkap Pemasangan,Status Berlangganan\n"
            . ",,Pelanggan Tanpa Alamat,Banyumas,1990-01-01,081234567890,budi@example.com,Paket 20 Mbps,20 Mbps,110000,,Selesai\n"
            . "PLG-2026-9005,3302190505950005,Pelanggan Valid,Banyumas,1995-05-05,0812999999,valid@example.com,Paket 20 Mbps,20 Mbps,110000,Batuanten RT 01 Cilongok,Selesai\n";

        $file = UploadedFile::fake()->createWithContent('invalid_rows.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(route('admin.pelanggan.import'), [
            'file' => $file,
            'update_existing' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('import_warnings');

        $this->assertDatabaseHas('orders', [
            'order_number' => 'PLG-2026-9005',
            'customer_name' => 'Pelanggan Valid',
        ]);

        $this->assertDatabaseMissing('orders', [
            'customer_name' => 'Pelanggan Tanpa Alamat',
        ]);
    }

    public function test_admin_can_import_from_html_xls_export_format(): void
    {
        $htmlXls = <<<HTML
<html>
<body>
<table>
    <thead>
        <tr>
            <th>No</th>
            <th>ID / No. Pelanggan</th>
            <th>No. KTP / NIK</th>
            <th>Nama Lengkap Pelanggan</th>
            <th>Tempat Lahir</th>
            <th>Tanggal Lahir</th>
            <th>Usia</th>
            <th>No. Handphone (WA)</th>
            <th>Alamat Email</th>
            <th>Jenis Layanan (Paket)</th>
            <th>Kecepatan</th>
            <th>Harga / Bulan (Rp)</th>
            <th>Alamat Lengkap Pemasangan</th>
            <th>Wilayah / Desa</th>
            <th>Status Berlangganan</th>
            <th>Tanggal Terdaftar</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>1</td>
            <td>PLG-2026-7001</td>
            <td>3302190707770007</td>
            <td>Joko Susilo XLS</td>
            <td>Banyumas</td>
            <td>1977-07-07</td>
            <td>49 th</td>
            <td>081377777777</td>
            <td>joko@example.com</td>
            <td>Paket 20 Mbps</td>
            <td>20 Mbps</td>
            <td>110.000</td>
            <td>Batuanten RT 04/RW 02 Kec. Cilongok</td>
            <td>Batuanten</td>
            <td>Selesai</td>
            <td>01/08/2026</td>
        </tr>
    </tbody>
</table>
</body>
</html>
HTML;

        $file = UploadedFile::fake()->createWithContent('export_pelanggan.xls', $htmlXls);

        $response = $this->actingAs($this->admin)->post(route('admin.pelanggan.import'), [
            'file' => $file,
            'update_existing' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('orders', [
            'order_number' => 'PLG-2026-7001',
            'customer_name' => 'Joko Susilo XLS',
            'id_card_number' => '3302190707770007',
        ]);
    }
}
