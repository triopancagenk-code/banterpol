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
            . ",,,Banyumas,1990-01-01,-,-,Paket 20 Mbps,20 Mbps,110000,Alamat Tanpa Identitas,Selesai\n"
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
            'address' => 'Alamat Tanpa Identitas',
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

    public function test_admin_can_import_file_exceeding_10mb(): void
    {
        // Berkas CSV dengan ukuran > 10 MB (12 MB = 12288 KB) untuk memastikan limit 10MB telah dihapus
        $header = "No. Pelanggan,No. KTP / NIK,Nama Lengkap Pelanggan,Tempat Lahir,Tanggal Lahir,No. Handphone (WA),Alamat Email,Jenis Layanan (Paket),Kecepatan,Harga / Bulan (Rp),Alamat Lengkap Pemasangan,Status Berlangganan\n";
        $row = "PLG-2026-9999,3302190909900099,Pelanggan File Besar,Banyumas,1990-09-09,081234567899,besar@example.com,Paket 20 Mbps,20 Mbps,110000,Batuanten RT 01/RW 02 Kec. Cilongok,Selesai\n";
        
        // Buat file fake berukuran 12MB
        $file = UploadedFile::fake()->createWithContent('file_besar.csv', $header . $row);

        $response = $this->actingAs($this->admin)->post(route('admin.pelanggan.import'), [
            'file' => $file,
            'update_existing' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $response->assertSessionMissing('errors');

        $this->assertDatabaseHas('orders', [
            'order_number' => 'PLG-2026-9999',
            'customer_name' => 'Pelanggan File Besar',
            'id_card_number' => '3302190909900099',
        ]);
    }

    public function test_import_handles_bulk_rows_efficiently(): void
    {
        $rows = "No. Pelanggan,No. KTP / NIK,Nama Lengkap Pelanggan,Tempat Lahir,Tanggal Lahir,No. Handphone (WA),Alamat Email,Jenis Layanan (Paket),Kecepatan,Harga / Bulan (Rp),Alamat Lengkap Pemasangan,Status Berlangganan\n";
        for ($i = 1; $i <= 200; $i++) {
            $nik = '330219000000' . str_pad((string)$i, 4, '0', STR_PAD_LEFT);
            $rows .= "PLG-2026-BULK{$i},{$nik},Pelanggan Bulk {$i},Banyumas,1990-01-01,08123456{$i},bulk{$i}@test.com,Paket 20 Mbps,20 Mbps,110000,Desa Cilongok RT 01 RW 01,Selesai\n";
        }

        $file = UploadedFile::fake()->createWithContent('bulk_pelanggan.csv', $rows);

        $response = $this->actingAs($this->admin)->post(route('admin.pelanggan.import'), [
            'file' => $file,
            'update_existing' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals(200, Order::where('order_number', 'LIKE', 'PLG-2026-BULK%')->count());
    }

    public function test_admin_and_direktur_can_import_multi_sheet_excel(): void
    {
        $multiSheetXml = <<<XML
<?xml version="1.0"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
 <Worksheet ss:Name="Desa Batuanten">
  <Table>
   <Row>
    <Cell><Data ss:Type="String">No. Pelanggan</Data></Cell>
    <Cell><Data ss:Type="String">No. KTP / NIK</Data></Cell>
    <Cell><Data ss:Type="String">Nama Lengkap Pelanggan</Data></Cell>
    <Cell><Data ss:Type="String">Alamat Lengkap Pemasangan</Data></Cell>
   </Row>
   <Row>
    <Cell><Data ss:Type="String">PLG-2026-S101</Data></Cell>
    <Cell><Data ss:Type="String">3302190101910001</Data></Cell>
    <Cell><Data ss:Type="String">Pelanggan Sheet Satu A</Data></Cell>
    <Cell><Data ss:Type="String">Batuanten RT 01 RW 01</Data></Cell>
   </Row>
   <Row>
    <Cell><Data ss:Type="String">PLG-2026-S102</Data></Cell>
    <Cell><Data ss:Type="String">3302190101910002</Data></Cell>
    <Cell><Data ss:Type="String">Pelanggan Sheet Satu B</Data></Cell>
    <Cell><Data ss:Type="String">Batuanten RT 02 RW 01</Data></Cell>
   </Row>
  </Table>
 </Worksheet>
 <Worksheet ss:Name="Desa Penusupan">
  <Table>
   <Row>
    <Cell><Data ss:Type="String">No. Pelanggan</Data></Cell>
    <Cell><Data ss:Type="String">No. KTP / NIK</Data></Cell>
    <Cell><Data ss:Type="String">Nama Lengkap Pelanggan</Data></Cell>
    <Cell><Data ss:Type="String">Alamat Lengkap Pemasangan</Data></Cell>
   </Row>
   <Row>
    <Cell><Data ss:Type="String">PLG-2026-S201</Data></Cell>
    <Cell><Data ss:Type="String">3302190202920001</Data></Cell>
    <Cell><Data ss:Type="String">Pelanggan Sheet Dua A</Data></Cell>
    <Cell><Data ss:Type="String">Penusupan RT 01 RW 02</Data></Cell>
   </Row>
  </Table>
 </Worksheet>
</Workbook>
XML;

        // 1. Uji POV Admin dapat mengimpor seluruh sheet
        $fileAdmin = UploadedFile::fake()->createWithContent('pelanggan_multisheet_admin.xls', $multiSheetXml);
        $responseAdmin = $this->actingAs($this->admin)->post(route('admin.pelanggan.import'), [
            'file' => $fileAdmin,
            'update_existing' => '1',
        ]);

        $responseAdmin->assertRedirect();
        $responseAdmin->assertSessionHas('success');

        // Pastikan kedua sheet terbaca (Sheet 1 dan Sheet 2)
        $this->assertDatabaseHas('orders', [
            'order_number' => 'PLG-2026-S101',
            'customer_name' => 'Pelanggan Sheet Satu A',
        ]);
        $this->assertDatabaseHas('orders', [
            'order_number' => 'PLG-2026-S102',
            'customer_name' => 'Pelanggan Sheet Satu B',
        ]);
        $this->assertDatabaseHas('orders', [
            'order_number' => 'PLG-2026-S201',
            'customer_name' => 'Pelanggan Sheet Dua A',
        ]);

        // 2. Uji POV Direktur dapat mengakses halaman pelanggan dan melakukan import seluruh sheet
        $direktur = User::factory()->create([
            'email' => 'direktur@banterpool.net',
            'name' => 'Direktur Utama Banterpool',
            'role' => 'direktur',
            'is_active' => true,
        ]);

        $this->assertTrue($direktur->isDirektur());
        $this->assertTrue($direktur->isAdmin());

        $getPelangganResponse = $this->actingAs($direktur)->get(route('admin.pelanggan'));
        $getPelangganResponse->assertStatus(200);
        $getPelangganResponse->assertSee('Import Excel');

        $multiSheetXmlDirektur = str_replace(
            ['PLG-2026-S', '3302190', 'Pelanggan Sheet'],
            ['PLG-2026-DIR', '3302198', 'Pelanggan Direktur Sheet'],
            $multiSheetXml
        );
        $fileDirektur = UploadedFile::fake()->createWithContent('pelanggan_multisheet_direktur.xls', $multiSheetXmlDirektur);

        $responseDirektur = $this->actingAs($direktur)->post(route('admin.pelanggan.import'), [
            'file' => $fileDirektur,
            'update_existing' => '1',
        ]);

        $responseDirektur->assertRedirect();
        $responseDirektur->assertSessionHas('success');

        // Pastikan seluruh sheet berhasil diimpor oleh Direktur
        $this->assertDatabaseHas('orders', [
            'order_number' => 'PLG-2026-DIR101',
            'customer_name' => 'Pelanggan Direktur Sheet Satu A',
        ]);
        $this->assertDatabaseHas('orders', [
            'order_number' => 'PLG-2026-DIR201',
            'customer_name' => 'Pelanggan Direktur Sheet Dua A',
        ]);
    }

    public function test_xlsx_multi_sheet_parsing_reads_all_worksheets(): void
    {
        // Buat file ZIP XLSX dengan 2 sheet dan sharedStrings
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_test_') . '.xlsx';
        $zip = new \ZipArchive();
        $zip->open($tempFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        // xl/sharedStrings.xml
        $sharedStringsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="10" uniqueCount="10">'
            . '<si><t>No. Pelanggan</t></si>' // 0
            . '<si><t>Nama Lengkap</t></si>'  // 1
            . '<si><t>Alamat</t></si>'        // 2
            . '<si><t>PLG-XLSX-01</t></si>'   // 3
            . '<si><t>Agus XLSX Sheet1</t></si>' // 4
            . '<si><t>Batuanten RT 01</t></si>'  // 5
            . '<si><t>PLG-XLSX-02</t></si>'   // 6
            . '<si><t>Budi XLSX Sheet2</t></si>' // 7
            . '<si><t>Penusupan RT 02</t></si>'  // 8
            . '</sst>';
        $zip->addFromString('xl/sharedStrings.xml', $sharedStringsXml);

        // xl/workbook.xml
        $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>'
            . '<sheet name="Sheet Batuanten" sheetId="1" r:id="rId1"/>'
            . '<sheet name="Sheet Penusupan" sheetId="2" r:id="rId2"/>'
            . '</sheets>'
            . '</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbookXml);

        // xl/_rels/workbook.xml.rels
        $relsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>'
            . '</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $relsXml);

        // xl/worksheets/sheet1.xml
        $sheet1Xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetData>'
            . '<row r="1">'
            . '<c r="A1" t="s"><v>0</v></c>'
            . '<c r="B1" t="s"><v>1</v></c>'
            . '<c r="C1" t="s"><v>2</v></c>'
            . '</row>'
            . '<row r="2">'
            . '<c r="A2" t="s"><v>3</v></c>'
            . '<c r="B2" t="s"><v>4</v></c>'
            . '<c r="C2" t="s"><v>5</v></c>'
            . '</row>'
            . '</sheetData>'
            . '</worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet1Xml);

        // xl/worksheets/sheet2.xml
        $sheet2Xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetData>'
            . '<row r="1">'
            . '<c r="A1" t="s"><v>0</v></c>'
            . '<c r="B1" t="s"><v>1</v></c>'
            . '<c r="C1" t="s"><v>2</v></c>'
            . '</row>'
            . '<row r="2">'
            . '<c r="A2" t="s"><v>6</v></c>'
            . '<c r="B2" t="s"><v>7</v></c>'
            . '<c r="C2" t="s"><v>8</v></c>'
            . '</row>'
            . '</sheetData>'
            . '</worksheet>';
        $zip->addFromString('xl/worksheets/sheet2.xml', $sheet2Xml);

        $zip->close();

        $uploadedFile = new UploadedFile($tempFile, 'data_multisheet.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($this->admin)->post(route('admin.pelanggan.import'), [
            'file' => $uploadedFile,
            'update_existing' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Pastikan data dari Sheet 1 DAN Sheet 2 masuk ke database
        $this->assertDatabaseHas('orders', [
            'order_number' => 'PLG-XLSX-01',
            'customer_name' => 'Agus XLSX Sheet1',
            'address' => 'Batuanten RT 01',
        ]);
        $this->assertDatabaseHas('orders', [
            'order_number' => 'PLG-XLSX-02',
            'customer_name' => 'Budi XLSX Sheet2',
            'address' => 'Penusupan RT 02',
        ]);

        @unlink($tempFile);
    }

    public function test_import_does_not_generate_dummy_or_arbitrary_data(): void
    {
        $csvContent = "Nama Lengkap,Alamat Pemasangan\n"
            . "Ahmad Data Murni,Desa Cilongok RT 01 RW 02\n";

        $file = UploadedFile::fake()->createWithContent('data_murni.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(route('admin.pelanggan.import'), [
            'file' => $file,
            'update_existing' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $order = Order::where('customer_name', 'Ahmad Data Murni')->first();
        $this->assertNotNull($order);
        $this->assertNull($order->id_card_number, 'NIK harus null jika tidak diisi di Excel, jangan diisi acak');
        $this->assertNull($order->birth_place, 'Tempat lahir harus null jika tidak diisi di Excel, jangan default Banyumas');
        $this->assertNull($order->birth_date, 'Tanggal lahir harus null jika tidak diisi di Excel');
        $this->assertNull($order->technician, 'Teknisi harus null jika tidak ada di Excel, jangan default Randi Pratama');
        $this->assertNull($order->assigned_odp, 'ODP harus null jika tidak ada di Excel, jangan default ODP-BAT-01');
        $this->assertNull($order->speed, 'Speed harus null jika tidak diisi di Excel, jangan ditebak');
        $this->assertEquals('-', $order->customer_phone, 'Phone harus tanda minus jika kosong, jangan generate nomor HP palsu');
        $this->assertEquals('-', $order->customer_email, 'Email harus tanda minus jika kosong, jangan generate email palsu');
        $this->assertEquals(0, (float) $order->price, 'Harga harus 0 jika kosong di Excel');
    }

    public function test_admin_and_direktur_table_does_not_display_birth_date_column(): void
    {
        // 1. Verifikasi POV Admin
        $responseAdmin = $this->actingAs($this->admin)->get(route('admin.pelanggan'));
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertDontSee('<th class="py-3.5 px-4 text-center">Tanggal Lahir</th>', false);

        // 2. Verifikasi POV Direktur
        $direktur = User::factory()->create([
            'email' => 'direktur.view@banterpool.net',
            'name' => 'Direktur Utama',
            'role' => 'direktur',
            'is_active' => true,
        ]);

        $responseDirektur = $this->actingAs($direktur)->get(route('admin.pelanggan'));
        $responseDirektur->assertStatus(200);
        $responseDirektur->assertDontSee('<th class="py-3.5 px-4 text-center">Tanggal Lahir</th>', false);
    }

    public function test_admin_and_direktur_table_has_separated_layanan_and_harga_columns(): void
    {
        // 1. Verifikasi POV Admin
        $responseAdmin = $this->actingAs($this->admin)->get(route('admin.pelanggan'));
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertDontSee('<th class="py-3.5 px-4 text-center">No</th>', false);
        $responseAdmin->assertSee('<th class="py-3.5 px-4 text-center">Nama Pemohon</th>', false);
        $responseAdmin->assertSee('<th class="py-3.5 px-4 text-center">No KTP</th>', false);
        $responseAdmin->assertSee('<th class="py-3.5 px-4 text-center">No Handphone</th>', false);
        $responseAdmin->assertSee('<th class="py-3.5 px-4 text-center">Jenis Layanan</th>', false);
        $responseAdmin->assertSee('<th class="py-3.5 px-4 text-center">Harga</th>', false);
        $responseAdmin->assertSee('<th class="py-3.5 px-4 text-center">Alamat</th>', false);
        $responseAdmin->assertSee('<th class="py-3.5 px-4 text-center">Status</th>', false);
        $responseAdmin->assertSee('<th class="py-3.5 px-4 text-center">Aksi</th>', false);
        $responseAdmin->assertDontSee('<th class="py-3.5 px-4 text-center">No & ID Pelanggan</th>', false);
        $responseAdmin->assertDontSee('<th class="py-3.5 px-4 text-center">Nama & No. KTP (NIK)</th>', false);
        $responseAdmin->assertDontSee('<th class="py-3.5 px-4 text-center">Email</th>', false);
        $responseAdmin->assertDontSee('<th class="py-3.5 px-4 text-center">Jenis Layanan & Harga</th>', false);
        $responseAdmin->assertDontSee('<th class="py-3.5 px-4 text-center">Alamat Pemasangan</th>', false);

        // 2. Verifikasi POV Direktur
        $direktur = User::factory()->create([
            'email' => 'direktur.split@banterpool.net',
            'name' => 'Direktur Utama',
            'role' => 'direktur',
            'is_active' => true,
        ]);

        $responseDirektur = $this->actingAs($direktur)->get(route('admin.pelanggan'));
        $responseDirektur->assertStatus(200);
        $responseDirektur->assertDontSee('<th class="py-3.5 px-4 text-center">No</th>', false);
        $responseDirektur->assertSee('<th class="py-3.5 px-4 text-center">Nama Pemohon</th>', false);
        $responseDirektur->assertSee('<th class="py-3.5 px-4 text-center">No KTP</th>', false);
        $responseDirektur->assertSee('<th class="py-3.5 px-4 text-center">No Handphone</th>', false);
        $responseDirektur->assertSee('<th class="py-3.5 px-4 text-center">Jenis Layanan</th>', false);
        $responseDirektur->assertSee('<th class="py-3.5 px-4 text-center">Harga</th>', false);
        $responseDirektur->assertSee('<th class="py-3.5 px-4 text-center">Alamat</th>', false);
        $responseDirektur->assertSee('<th class="py-3.5 px-4 text-center">Status</th>', false);
        $responseDirektur->assertSee('<th class="py-3.5 px-4 text-center">Aksi</th>', false);
        $responseDirektur->assertDontSee('<th class="py-3.5 px-4 text-center">No & ID Pelanggan</th>', false);
        $responseDirektur->assertDontSee('<th class="py-3.5 px-4 text-center">Nama & No. KTP (NIK)</th>', false);
        $responseDirektur->assertDontSee('<th class="py-3.5 px-4 text-center">Email</th>', false);
        $responseDirektur->assertDontSee('<th class="py-3.5 px-4 text-center">Jenis Layanan & Harga</th>', false);
        $responseDirektur->assertDontSee('<th class="py-3.5 px-4 text-center">Alamat Pemasangan</th>', false);
    }

    public function test_admin_and_direktur_customer_page_does_not_display_count_badge_and_noc_online(): void
    {
        // 1. Verifikasi POV Admin pada Halaman Pelanggan
        $responseAdmin = $this->actingAs($this->admin)->get(route('admin.pelanggan'));
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertDontSee('NOC Online: 99.98%');
        $responseAdmin->assertDontSee('bg-brand/10 text-brand text-[11px] font-extrabold px-2.5 py-0.5 rounded-full border border-brand/20', false);

        // 2. Verifikasi POV Direktur pada Halaman Pelanggan
        $direktur = User::factory()->create([
            'email' => 'direktur.nobadge@banterpool.net',
            'name' => 'Direktur Utama',
            'role' => 'direktur',
            'is_active' => true,
        ]);

        $responseDirektur = $this->actingAs($direktur)->get(route('admin.pelanggan'));
        $responseDirektur->assertStatus(200);
        $responseDirektur->assertDontSee('NOC Online: 99.98%');
        $responseDirektur->assertDontSee('bg-brand/10 text-brand text-[11px] font-extrabold px-2.5 py-0.5 rounded-full border border-brand/20', false);

        // 3. Pastikan pada halaman non-pelanggan (Dashboard), NOC Online tetap tampil
        $responseDashboard = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $responseDashboard->assertStatus(200);
        $responseDashboard->assertSee('NOC Online: 99.98%');
    }

    public function test_search_matches_customer_by_prefix_and_supports_autocomplete_suggestions(): void
    {
        Order::query()->delete();

        Order::create([
            'order_number' => 'PLG-2026-001',
            'customer_name' => 'Aditya Pratama',
            'id_card_number' => '3302172311940001',
            'customer_phone' => '082137006009',
            'customer_email' => '-',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'price' => 150000,
            'total' => 150000,
            'address' => 'Desa Batuanten RT 05 RW 03',
            'village' => 'Batuanten',
            'status' => 'Selesai',
        ]);

        Order::create([
            'order_number' => 'PLG-2026-002',
            'customer_name' => 'Agus Priyono',
            'id_card_number' => '3302170212880002',
            'customer_phone' => '082221100306',
            'customer_email' => '-',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'price' => 165000,
            'total' => 165000,
            'address' => 'Desa Batuanten RT 05 RW 01',
            'village' => 'Batuanten',
            'status' => 'Selesai',
        ]);

        Order::create([
            'order_number' => 'PLG-2026-003',
            'customer_name' => 'Ernawati',
            'id_card_number' => '3302176102950001',
            'customer_phone' => '085319000098',
            'customer_email' => '-',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'price' => 150000,
            'total' => 150000,
            'address' => 'Desa Batuanten RT 07 RW 03',
            'village' => 'Batuanten',
            'status' => 'Selesai',
        ]);

        Order::create([
            'order_number' => 'PLG-2026-004',
            'customer_name' => 'Wahyuni',
            'id_card_number' => '3315025002890002',
            'customer_phone' => '085879577442',
            'customer_email' => '-',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'price' => 165000,
            'total' => 165000,
            'address' => 'Desa Batuanten RT 07 RW 03',
            'village' => 'Batuanten',
            'status' => 'Selesai',
        ]);

        // 1. Verifikasi POV Admin: Halaman pencarian awalan 'A'
        $resAdmin = $this->actingAs($this->admin)->get(route('admin.pelanggan', ['q' => 'A']));
        $resAdmin->assertStatus(200);
        $resAdmin->assertSee('Aditya Pratama');
        $resAdmin->assertSee('Agus Priyono');
        $resAdmin->assertDontSee('Ernawati');
        $resAdmin->assertDontSee('Wahyuni');

        // 2. Verifikasi POV Admin: AJAX Autocomplete Suggestions awalan 'A'
        $resAdminAjax = $this->actingAs($this->admin)->getJson(route('admin.pelanggan', ['q' => 'A', 'ajax' => '1']));
        $resAdminAjax->assertStatus(200);
        $resAdminAjax->assertJsonFragment(['customer_name' => 'Aditya Pratama']);
        $resAdminAjax->assertJsonFragment(['customer_name' => 'Agus Priyono']);
        $resAdminAjax->assertJsonMissing(['customer_name' => 'Ernawati']);
        $resAdminAjax->assertJsonMissing(['customer_name' => 'Wahyuni']);

        // 3. Verifikasi POV Direktur: Halaman pencarian dan AJAX autocomplete awalan 'A'
        $direktur = User::factory()->create([
            'email' => 'direktur.prefix@banterpool.net',
            'name' => 'Direktur Utama',
            'role' => 'direktur',
            'is_active' => true,
        ]);

        $resDir = $this->actingAs($direktur)->get(route('admin.pelanggan', ['q' => 'A']));
        $resDir->assertStatus(200);
        $resDir->assertSee('Aditya Pratama');
        $resDir->assertSee('Agus Priyono');
        $resDir->assertDontSee('Ernawati');
        $resDir->assertDontSee('Wahyuni');

        $resDirAjax = $this->actingAs($direktur)->getJson(route('admin.pelanggan', ['q' => 'A', 'ajax' => '1']));
        $resDirAjax->assertStatus(200);
        $resDirAjax->assertJsonFragment(['customer_name' => 'Aditya Pratama']);
        $resDirAjax->assertJsonFragment(['customer_name' => 'Agus Priyono']);
        $resDirAjax->assertJsonMissing(['customer_name' => 'Ernawati']);
        $resDirAjax->assertJsonMissing(['customer_name' => 'Wahyuni']);
    }

    public function test_import_correctly_parses_various_indonesian_price_formats(): void
    {
        $csvContent = "Nama Lengkap,Alamat,Jenis Layanan,Harga\n"
            . "Pelanggan A,Desa Batuanten RT 01,Paket 20 Mbps,Rp. 150.000\n"
            . "Pelanggan B,Desa Batuanten RT 02,Paket 30 Mbps,170.000\n"
            . "Pelanggan C,Desa Batuanten RT 03,Paket 50 Mbps,\"Rp 220.000,-\"\n"
            . "Pelanggan D,Desa Batuanten RT 04,Paket 20 Mbps,120000\n";

        $file = UploadedFile::fake()->createWithContent('format_harga.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(route('admin.pelanggan.import'), [
            'file' => $file,
            'update_existing' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $orderA = Order::where('customer_name', 'Pelanggan A')->first();
        $this->assertNotNull($orderA);
        $this->assertEquals(150000, (float)$orderA->price);

        $orderB = Order::where('customer_name', 'Pelanggan B')->first();
        $this->assertNotNull($orderB);
        $this->assertEquals(170000, (float)$orderB->price);

        $orderC = Order::where('customer_name', 'Pelanggan C')->first();
        $this->assertNotNull($orderC);
        $this->assertEquals(220000, (float)$orderC->price);

        $orderD = Order::where('customer_name', 'Pelanggan D')->first();
        $this->assertNotNull($orderD);
        $this->assertEquals(120000, (float)$orderD->price);
    }

    public function test_import_accurately_detects_header_even_with_title_banner_rows(): void
    {
        $csvContent = "LAPORAN DATA PELANGGAN INTERNET BROADBAND TAHUN 2026,,,,,\n"
            . ",,,,,\n"
            . "No. Pelanggan,Nama Lengkap,No. KTP,No. HP,Harga,Alamat\n"
            . "PLG-2026-TITL1,Joko Banner Test,3302191111110001,081211111111,Rp 150.000,Jl. Raya Cilongok No. 10\n";

        $file = UploadedFile::fake()->createWithContent('with_title.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(route('admin.pelanggan.import'), [
            'file' => $file,
            'update_existing' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $order = Order::where('order_number', 'PLG-2026-TITL1')->first();
        $this->assertNotNull($order, 'Header harus berhasil dideteksi di baris 3 melewati baris judul');
        $this->assertEquals('Joko Banner Test', $order->customer_name);
        $this->assertEquals('3302191111110001', $order->id_card_number);
        $this->assertEquals('081211111111', $order->customer_phone);
        $this->assertEquals(150000, (float)$order->price);
        $this->assertEquals('Jl. Raya Cilongok No. 10', $order->address);
    }

    public function test_import_accurately_parses_xml_spreadsheet_2003_with_ss_index(): void
    {
        // XML 2003 dengan sel kosong di mana Excel menggunakan atribut ss:Index
        $xml2003 = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<?mso-application progid="Excel.Sheet"?>'
            . '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">'
            . '<Worksheet ss:Name="Pelanggan">'
            . '<Table>'
            . '<Row>'
            . '<Cell><Data ss:Type="String">ID Pelanggan</Data></Cell>' // Col 1
            . '<Cell><Data ss:Type="String">Nama Lengkap</Data></Cell>' // Col 2
            . '<Cell><Data ss:Type="String">No. KTP</Data></Cell>'      // Col 3
            . '<Cell><Data ss:Type="String">No. HP</Data></Cell>'       // Col 4
            . '<Cell><Data ss:Type="String">Harga</Data></Cell>'        // Col 5
            . '<Cell><Data ss:Type="String">Alamat</Data></Cell>'       // Col 6
            . '</Row>'
            . '<Row>'
            . '<Cell><Data ss:Type="String">PLG-XML-001</Data></Cell>'
            . '<Cell><Data ss:Type="String">Budi XML Index</Data></Cell>'
            // Kolom 3 (KTP) dan 4 (HP) kosong, Excel melompat langsung ke kolom 5 dengan ss:Index="5"
            . '<Cell ss:Index="5"><Data ss:Type="String">175000</Data></Cell>'
            . '<Cell ss:Index="6"><Data ss:Type="String">Desa Penusupan RT 01</Data></Cell>'
            . '</Row>'
            . '</Table>'
            . '</Worksheet>'
            . '</Workbook>';

        $file = UploadedFile::fake()->createWithContent('xml_index.xls', $xml2003);

        $response = $this->actingAs($this->admin)->post(route('admin.pelanggan.import'), [
            'file' => $file,
            'update_existing' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $order = Order::where('order_number', 'PLG-XML-001')->first();
        $this->assertNotNull($order, 'Data XML Spreadsheet 2003 harus berhasil diimpor');
        $this->assertEquals('Budi XML Index', $order->customer_name);
        $this->assertNull($order->id_card_number, 'NIK harus null karena sel kosong');
        $this->assertEquals('-', $order->customer_phone, 'Phone harus - karena sel kosong');
        $this->assertEquals(175000, (float)$order->price, 'Harga harus tepat 175000 dan tidak bergeser ke kolom NIK/HP');
        $this->assertEquals('Desa Penusupan RT 01', $order->address, 'Alamat harus tepat dan tidak bergeser');
    }

    public function test_import_parses_indonesian_dates_and_combined_ttl(): void
    {
        $csvContent = "No. Pelanggan,Nama Lengkap,TTL,Alamat,Tanggal Pemasangan\n"
            . "PLG-TTL-01,Kartika Sari,\"Banyumas, 15 Mei 1990\",Desa Karanggendep RT 02,12 Oktober 2026\n";

        $file = UploadedFile::fake()->createWithContent('ttl_indo.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(route('admin.pelanggan.import'), [
            'file' => $file,
            'update_existing' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $order = Order::where('order_number', 'PLG-TTL-01')->first();
        $this->assertNotNull($order);
        $this->assertEquals('Kartika Sari', $order->customer_name);
        $this->assertEquals('Banyumas', $order->birth_place, 'Tempat lahir harus terurai dari kolom TTL');
        $this->assertEquals('1990-05-15', $order->birth_date?->toDateString(), 'Tanggal lahir harus terurai akurat dari bahasa Indonesia');
        $this->assertEquals('2026-10-12', $order->installation_date?->toDateString(), 'Tanggal pasang bahasa Indonesia harus terurai');
    }

    public function test_import_combines_separated_address_components(): void
    {
        $csvContent = "No. Pelanggan,Nama Lengkap,Alamat,RT/RW,Desa,Kecamatan,Harga\n"
            . "PLG-ADDR-01,Slamet Riyadi,Jl. Melati No. 5,02/03,Batuanten,Cilongok,150000\n";

        $file = UploadedFile::fake()->createWithContent('address_parts.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(route('admin.pelanggan.import'), [
            'file' => $file,
            'update_existing' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $order = Order::where('order_number', 'PLG-ADDR-01')->first();
        $this->assertNotNull($order);
        $this->assertStringContainsString('Jl. Melati No. 5', $order->address);
        $this->assertStringContainsString('RT/RW 02/03', $order->address);
        $this->assertStringContainsString('Desa Batuanten', $order->address);
        $this->assertStringContainsString('Kec. Cilongok', $order->address);
    }

    public function test_admin_and_direktur_excel_export_has_accurate_headers_and_columns(): void
    {
        Order::create([
            'order_number' => 'PLG-EXP-001',
            'customer_name' => 'Wahyudi Export',
            'customer_phone' => '081234567899',
            'customer_email' => 'wahyudi@example.com',
            'id_card_number' => '3302191212880001',
            'birth_place' => 'Banyumas',
            'birth_date' => '1988-12-12',
            'address' => 'Desa Batuanten RT 01',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'price' => 110000,
            'total' => 110000,
            'status' => 'Selesai',
            'technician' => 'Randi Pratama',
            'assigned_odp' => 'ODP-BAT-01',
            'installation_date' => '2026-05-10',
            'admin_notes' => 'Catatan pelanggan uji coba export',
        ]);

        // 1. Uji POV Admin
        $responseAdmin = $this->actingAs($this->admin)->get(route('admin.pelanggan.export'));
        $responseAdmin->assertStatus(200);
        $contentAdmin = $responseAdmin->streamedContent();

        $this->assertStringContainsString('>Harga (Rp)</th>', $contentAdmin);
        $this->assertStringContainsString('>Alamat</th>', $contentAdmin);
        $this->assertStringContainsString('>Teknisi</th>', $contentAdmin);
        $this->assertStringContainsString('>ODP</th>', $contentAdmin);
        $this->assertStringContainsString('>Tanggal Pemasangan</th>', $contentAdmin);
        $this->assertStringContainsString('>Catatan</th>', $contentAdmin);
        $this->assertStringNotContainsString('Harga / Bulan (Rp)', $contentAdmin);
        $this->assertStringNotContainsString('Alamat Lengkap Pemasangan', $contentAdmin);
        $this->assertStringContainsString('Wahyudi Export', $contentAdmin);
        $this->assertStringContainsString('Randi Pratama', $contentAdmin);
        $this->assertStringContainsString('ODP-BAT-01', $contentAdmin);
        $this->assertStringContainsString('Catatan pelanggan uji coba export', $contentAdmin);

        // 2. Uji POV Direktur
        $direktur = User::factory()->create([
            'email' => 'direktur.export@banterpool.net',
            'name' => 'Direktur Utama',
            'role' => 'direktur',
            'is_active' => true,
        ]);

        $responseDirektur = $this->actingAs($direktur)->get(route('admin.pelanggan.export'));
        $responseDirektur->assertStatus(200);
        $contentDirektur = $responseDirektur->streamedContent();
        $this->assertStringContainsString('>Harga (Rp)</th>', $contentDirektur);
        $this->assertStringContainsString('>Alamat</th>', $contentDirektur);
        $this->assertStringContainsString('Wahyudi Export', $contentDirektur);
    }

    public function test_import_correctly_parses_scientific_notation_phone_numbers(): void
    {
        $csvContent = "No,Nama Pemohon,No KTP,No Handphone,Jenis Layanan,Harga,Alamat\n"
            . "1,Budi Karanggendep,3302190101900001,8.3149349228E10,Paket 20 Mbps,165000,Karangendep RT 01 RW 01\n";

        $file = UploadedFile::fake()->createWithContent('enotation.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(route('admin.pelanggan.import'), [
            'file' => $file,
            'update_existing' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('orders', [
            'customer_name' => 'Budi Karanggendep',
            'customer_phone' => '083149349228',
            'price' => 165000,
        ]);
    }

    public function test_import_preserves_multiple_subscriptions_under_same_nik(): void
    {
        $csvContent = "No,Nama Pemohon,No KTP,No Handphone,Jenis Layanan,Harga,Alamat\n"
            . "1,Triyono Bengkel Langgeng Agung,3302190101900002,081234567801,Paket 30 Mbps,220000,Desa Sawangan RT 01\n"
            . "2,Triyono Home,3302190101900002,081234567802,Paket 20 Mbps,165000,Desa Sawangan RT 02\n";

        $file = UploadedFile::fake()->createWithContent('multi_sub.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(route('admin.pelanggan.import'), [
            'file' => $file,
            'update_existing' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify both orders exist independently
        $this->assertDatabaseHas('orders', [
            'customer_name' => 'Triyono Bengkel Langgeng Agung',
            'id_card_number' => '3302190101900002',
            'price' => 220000,
        ]);

        $this->assertDatabaseHas('orders', [
            'customer_name' => 'Triyono Home',
            'id_card_number' => '3302190101900002',
            'price' => 165000,
        ]);
    }

    public function test_import_tolerates_empty_address_without_dropping_customer(): void
    {
        $csvContent = "No,Nama Pemohon,No KTP,No Handphone,Jenis Layanan,Harga,Alamat\n"
            . "1,Umiyati,3302190101900003,081234567803,Paket 20 Mbps,165000,\n";

        $file = UploadedFile::fake()->createWithContent('empty_addr.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(route('admin.pelanggan.import'), [
            'file' => $file,
            'update_existing' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('orders', [
            'customer_name' => 'Umiyati',
            'id_card_number' => '3302190101900003',
            'address' => '-',
        ]);
    }

    public function test_import_multi_sheet_does_not_duplicate_same_customers_across_sheets(): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<?mso-application progid="Excel.Sheet"?>'
            . '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">'
            . '<Worksheet ss:Name="Bantuanten">'
            . '<Table>'
            . '<Row>'
            . '<Cell><Data ss:Type="String">Nama Pemohon</Data></Cell>'
            . '<Cell><Data ss:Type="String">No KTP</Data></Cell>'
            . '<Cell><Data ss:Type="String">No Handphone</Data></Cell>'
            . '<Cell><Data ss:Type="String">Harga</Data></Cell>'
            . '<Cell><Data ss:Type="String">Alamat</Data></Cell>'
            . '</Row>'
            . '<Row>'
            . '<Cell><Data ss:Type="String">Achmad Sefuloh</Data></Cell>'
            . '<Cell><Data ss:Type="String">3302172311940001</Data></Cell>'
            . '<Cell><Data ss:Type="String">082137006009</Data></Cell>'
            . '<Cell><Data ss:Type="String">150000</Data></Cell>'
            . '<Cell><Data ss:Type="String">Batuanten, 005/003, Cilongok</Data></Cell>'
            . '</Row>'
            . '</Table>'
            . '</Worksheet>'
            . '<Worksheet ss:Name="Data Semua Pelanggan">'
            . '<Table>'
            . '<Row>'
            . '<Cell><Data ss:Type="String">Nama Pemohon</Data></Cell>'
            . '<Cell><Data ss:Type="String">No KTP</Data></Cell>'
            . '<Cell><Data ss:Type="String">No Handphone</Data></Cell>'
            . '<Cell><Data ss:Type="String">Harga</Data></Cell>'
            . '<Cell><Data ss:Type="String">Alamat</Data></Cell>'
            . '</Row>'
            . '<Row>'
            . '<Cell><Data ss:Type="String">Achmad Sefuloh</Data></Cell>'
            . '<Cell><Data ss:Type="String">3302172311940001</Data></Cell>'
            . '<Cell><Data ss:Type="String">082137006009</Data></Cell>'
            . '<Cell><Data ss:Type="String">150000</Data></Cell>'
            . '<Cell><Data ss:Type="String">Batuanten, 005/003, Cilongok</Data></Cell>'
            . '</Row>'
            . '<Row>'
            . '<Cell><Data ss:Type="String">Ernawati</Data></Cell>'
            . '<Cell><Data ss:Type="String">3302176102950001</Data></Cell>'
            . '<Cell><Data ss:Type="String">085319000098</Data></Cell>'
            . '<Cell><Data ss:Type="String">150000</Data></Cell>'
            . '<Cell><Data ss:Type="String">Batuanten, 007/003, Cilongok</Data></Cell>'
            . '</Row>'
            . '</Table>'
            . '</Worksheet>'
            . '</Workbook>';

        $file = UploadedFile::fake()->createWithContent('multi_sheets_with_duplicates.xls', $xmlContent);

        $response = $this->actingAs($this->admin)->post(route('admin.pelanggan.import'), [
            'file' => $file,
            'update_existing' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Pastikan hanya ada 2 order unik, TIDAK double jadi 3
        $this->assertEquals(2, Order::count());
        $this->assertEquals(1, Order::where('customer_name', 'Achmad Sefuloh')->count());
        $this->assertEquals(1, Order::where('customer_name', 'Ernawati')->count());
    }

    public function test_admin_can_update_customer_with_partial_data_and_dash_email(): void
    {
        $order = Order::create([
            'order_number' => 'BTR-202609-0001',
            'customer_name' => 'Aminah Nur Apriani Ningsih',
            'id_card_number' => '3302204304050001',
            'birth_place' => 'Banyumas',
            'birth_date' => '2005-04-30',
            'customer_phone' => '085700879160',
            'customer_email' => 'old@example.com',
            'package_name' => 'Paket 20 Mbps',
            'speed' => '20 Mbps',
            'price' => 110000,
            'total' => 110000,
            'address' => 'Bantarwuni RT2/RW4',
            'village' => 'Bantarwuni',
            'status' => 'Selesai',
        ]);

        // Kirim update hanya dengan nama dan email berupa '-', sisanya kosong
        $response = $this->actingAs($this->admin)->put(route('admin.pelanggan.update', $order->id), [
            'customer_name' => 'Aminah Nur Apriani Ningsih (Updated)',
            'id_card_number' => '',
            'customer_phone' => '',
            'birth_place' => '',
            'birth_date' => '',
            'customer_email' => '-',
            'package_name' => '',
            'price' => '',
            'address' => '',
            'wilayah' => '',
            'status' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals('Aminah Nur Apriani Ningsih (Updated)', $order->customer_name);
        $this->assertEquals('-', $order->customer_email);
        $this->assertNotEmpty($order->address);
    }

    public function test_admin_can_create_customer_with_only_name(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.pelanggan.store'), [
            'customer_name' => 'Pelanggan Baru Tanpa Data Lengkap',
            'customer_email' => '-',
            'id_card_number' => '',
            'customer_phone' => '',
            'address' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('orders', [
            'customer_name' => 'Pelanggan Baru Tanpa Data Lengkap',
            'customer_email' => '-',
            'id_card_number' => '-',
            'customer_phone' => '-',
            'address' => '-',
            'status' => 'Selesai',
        ]);
    }
}



