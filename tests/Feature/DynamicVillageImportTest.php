<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class DynamicVillageImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin.village@banterpool.net',
            'role' => 'admin',
        ]);
    }

    public function test_hardcoded_empty_villages_do_not_appear_in_pelanggan_view(): void
    {
        // Buat pelanggan hanya untuk Batuanten dan Sawangan
        Order::create([
            'order_number' => 'PLG-TEST-001',
            'customer_name' => 'Warga Batuanten',
            'customer_phone' => '081234567890',
            'customer_email' => 'warga1@example.com',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'address' => 'Batuanten RT 01/RW 02',
            'village' => 'Batuanten',
            'price' => 110000,
            'total' => 110000,
            'status' => 'Selesai',
        ]);

        Order::create([
            'order_number' => 'PLG-TEST-002',
            'customer_name' => 'Warga Sawangan',
            'customer_phone' => '085712345678',
            'customer_email' => 'warga2@example.com',
            'package_name' => 'Paket 30 Mbps',
            'speed' => '30 Mbps',
            'address' => 'Desa Sawangan RT 03/RW 01',
            'village' => 'Sawangan',
            'price' => 165000,
            'total' => 165000,
            'status' => 'Selesai',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.pelanggan'));
        $response->assertStatus(200);

        // Harus menampilkan desa yang memiliki pelanggan
        $response->assertSee('Batuanten (1 Pelanggan)');
        $response->assertSee('Sawangan (1 Pelanggan)');

        // TIDAK BOLEH menampilkan desa statis/hardcoded yang 0 pelanggan (Cipete, Pageraji)
        $response->assertDontSee('Cipete (0 Pelanggan)');
        $response->assertDontSee('Pageraji (0 Pelanggan)');
        $response->assertDontSee('Kasegeran (0 Pelanggan)');
    }

    public function test_import_with_custom_village_dynamically_populates_filter(): void
    {
        $csvContent = "No. Pelanggan,No. KTP / NIK,Nama Lengkap Pelanggan,No. Handphone (WA),Alamat Email,Jenis Layanan (Paket),Harga / Bulan (Rp),Alamat Lengkap Pemasangan,Desa,Status Berlangganan\n"
            . "PLG-NEW-001,3302190101900011,Budi Melati,081234567891,budi.melati@example.com,Paket 20 Mbps,110000,Jl Melati No 1,Desa Melati,Selesai\n"
            . "PLG-NEW-002,3302190202920022,Siti Mawar,085712345672,siti.mawar@example.com,Paket 30 Mbps,165000,Jl Mawar No 2,Desa Mawar,Selesai\n";

        $file = UploadedFile::fake()->createWithContent('desa_baru.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(route('admin.pelanggan.import'), [
            'file' => $file,
            'update_existing' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Pastikan kolom village diisi otomatis dan bersih
        $this->assertDatabaseHas('orders', [
            'order_number' => 'PLG-NEW-001',
            'customer_name' => 'Budi Melati',
            'village' => 'Melati',
        ]);

        $this->assertDatabaseHas('orders', [
            'order_number' => 'PLG-NEW-002',
            'customer_name' => 'Siti Mawar',
            'village' => 'Mawar',
        ]);

        // Halaman admin harus otomatis menampilkan desa baru di filter dan pills
        $pageResponse = $this->actingAs($this->admin)->get(route('admin.pelanggan'));
        $pageResponse->assertStatus(200);
        $pageResponse->assertSee('Melati (1 Pelanggan)');
        $pageResponse->assertSee('Mawar (1 Pelanggan)');

        // Filter per desa baru
        $filterMelati = $this->actingAs($this->admin)->get(route('admin.pelanggan', ['wilayah' => 'Melati']));
        $filterMelati->assertStatus(200);
        $filterMelati->assertSee('Budi Melati');
        $filterMelati->assertDontSee('Siti Mawar');

        $filterMawar = $this->actingAs($this->admin)->get(route('admin.pelanggan', ['wilayah' => 'Mawar']));
        $filterMawar->assertStatus(200);
        $filterMawar->assertSee('Siti Mawar');
        $filterMawar->assertDontSee('Budi Melati');
    }

    public function test_import_with_template_pelanggan_sheet_name_detects_village_from_address_not_sheet_name(): void
    {
        $multiSheetXml = <<<XML
<?xml version="1.0"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
 <Worksheet ss:Name="Template Pelanggan">
  <Table>
   <Row>
    <Cell><Data ss:Type="String">No. KTP / NIK *</Data></Cell>
    <Cell><Data ss:Type="String">Nama Lengkap Pelanggan *</Data></Cell>
    <Cell><Data ss:Type="String">No. Handphone (WA) *</Data></Cell>
    <Cell><Data ss:Type="String">Jenis Layanan (Paket)</Data></Cell>
    <Cell><Data ss:Type="String">Harga (Rp)</Data></Cell>
    <Cell><Data ss:Type="String">Alamat *</Data></Cell>
   </Row>
   <Row>
    <Cell><Data ss:Type="String">3302190101910001</Data></Cell>
    <Cell><Data ss:Type="String">Pelanggan Batuanten Asli</Data></Cell>
    <Cell><Data ss:Type="String">081234567801</Data></Cell>
    <Cell><Data ss:Type="String">20 Mbps</Data></Cell>
    <Cell><Data ss:Type="String">110000</Data></Cell>
    <Cell><Data ss:Type="String">Batuanten RT 01 RW 01</Data></Cell>
   </Row>
   <Row>
    <Cell><Data ss:Type="String">3302190101910002</Data></Cell>
    <Cell><Data ss:Type="String">Pelanggan Panusupan Asli</Data></Cell>
    <Cell><Data ss:Type="String">081234567802</Data></Cell>
    <Cell><Data ss:Type="String">30 Mbps</Data></Cell>
    <Cell><Data ss:Type="String">165000</Data></Cell>
    <Cell><Data ss:Type="String">Panusupan RT 02 RW 01</Data></Cell>
   </Row>
  </Table>
 </Worksheet>
</Workbook>
XML;

        $file = UploadedFile::fake()->createWithContent('Template_Pelanggan.xls', $multiSheetXml);
        $response = $this->actingAs($this->admin)->post(route('admin.pelanggan.import'), [
            'file' => $file,
            'update_existing' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Pastikan village di database adalah nama desa dari alamat, BUKAN 'Template Pelanggan'
        $this->assertDatabaseHas('orders', [
            'customer_name' => 'Pelanggan Batuanten Asli',
            'village' => 'Batuanten',
        ]);
        $this->assertDatabaseHas('orders', [
            'customer_name' => 'Pelanggan Panusupan Asli',
            'village' => 'Penusupan',
        ]);

        $this->assertDatabaseMissing('orders', [
            'village' => 'Template Pelanggan',
        ]);

        // Halaman admin harus menampilkan 'Batuanten' dan 'Penusupan', bukan 'Template Pelanggan'
        $pageResponse = $this->actingAs($this->admin)->get(route('admin.pelanggan'));
        $pageResponse->assertStatus(200);
        $pageResponse->assertDontSee('Template Pelanggan (');
        $pageResponse->assertSee('Batuanten (1 Pelanggan)');
        $pageResponse->assertSee('Penusupan (1 Pelanggan)');
    }
}
