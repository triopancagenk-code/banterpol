<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\User;
use Database\Seeders\MaterialCsvDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_material_stock_page_and_see_csv_data(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->seed(MaterialCsvDataSeeder::class);

        // 1. Tab Laporan Stok Barang
        $response = $this->actingAs($admin)->get(route('admin.material.index', ['tab' => 'laporan']));
        $response->assertStatus(200);
        $response->assertSee('Laporan & Manajemen Stok Barang', false);
        $response->assertSee('A001');
        $response->assertSee('A050');
        $response->assertSee('Patchcore Biru');
        $response->assertSee('DC 1C');
        $response->assertSee('Stok Awal');
        $response->assertSee('Barang Masuk');
        $response->assertSee('Barang Keluar');
        $response->assertSee('Stok Akhir');

        // 2. Tab Barang Masuk
        $responseIn = $this->actingAs($admin)->get(route('admin.material.index', ['tab' => 'masuk']));
        $responseIn->assertStatus(200);
        $responseIn->assertSee('Pak Mono');
        $responseIn->assertSee('Aris');

        // 3. Tab Barang Keluar
        $responseOut = $this->actingAs($admin)->get(route('admin.material.index', ['tab' => 'keluar']));
        $responseOut->assertStatus(200);
        $responseOut->assertSee('Okta_ThreeFive');
        $responseOut->assertSee('Bagas_ThreeFive');

        // 4. Tab Spreadsheet Side-by-Side sudah dihilangkan
        $responseLaporan = $this->actingAs($admin)->get(route('admin.material.index', ['tab' => 'laporan']));
        $responseLaporan->assertDontSee('Tampilan Berdampingan (Sesuai CSV)');

        // 5. Test Filter Bulan Berbeda (September 2026 & Semua Periode)
        $responseSep = $this->actingAs($admin)->get(route('admin.material.index', ['tab' => 'laporan', 'month' => 9, 'year' => 2026]));
        $responseSep->assertStatus(200);
        $responseSep->assertSee('September 2026');
        $responseSep->assertSee('id="filter-month"', false);
        $responseSep->assertSee('id="filter-status"', false);

        // 6. Test Filter Status Dropdown (Aman, Menipis, Habis, Minus)
        $responseAman = $this->actingAs($admin)->get(route('admin.material.index', ['tab' => 'laporan', 'status' => 'aman']));
        $responseAman->assertStatus(200);

        $responseMinus = $this->actingAs($admin)->get(route('admin.material.index', ['tab' => 'laporan', 'status' => 'minus']));
        $responseMinus->assertStatus(200);

        $responseAll = $this->actingAs($admin)->get(route('admin.material.index', ['tab' => 'laporan', 'month' => 'all']));
        $responseAll->assertStatus(200);
        $responseAll->assertSee('Semua Periode');


        // 6. Test Export CSV & Excel dengan Scope Bulan
        $responseCsv = $this->actingAs($admin)->get(route('admin.material.export-csv', ['month' => 10, 'year' => 2026]));
        $responseCsv->assertStatus(200);
        $responseCsv->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $responseExcel = $this->actingAs($admin)->get(route('admin.material.export', ['month' => 10, 'year' => 2026]));
        $responseExcel->assertStatus(200);
    }

    public function test_table_shows_actual_material_name_not_month_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->seed(MaterialCsvDataSeeder::class);

        $res = $this->actingAs($admin)->get(route('admin.material.index', ['tab' => 'laporan']));
        $res->assertStatus(200);

        // Verify that the table header exists and material rows contain actual material names
        $res->assertSee('Nama Barang');
        $res->assertSee('Tiang T7');
        $res->assertSee('Slack Suport-40cm');

        // Check materials passed to the view
        $materials = $res->viewData('materials');
        $this->assertNotEmpty($materials);
        foreach ($materials as $item) {
            $this->assertNotEquals('Januari', $item->name, "Material name should not be month name 'Januari'");
            $this->assertNotEquals('Oktober', $item->name, "Material name should not be month name 'Oktober'");
        }
    }

    public function test_filters_isolate_only_matching_records(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->seed(MaterialCsvDataSeeder::class);

        // 1. Filter Category
        $resCat = $this->actingAs($admin)->get(route('admin.material.index', [
            'tab' => 'laporan',
            'category' => 'Tiang & Bracket',
        ]));
        $resCat->assertStatus(200);
        $catItems = $resCat->viewData('materials');
        $this->assertGreaterThan(0, $catItems->count());
        foreach ($catItems as $item) {
            $this->assertEquals('Tiang & Bracket', $item->category);
        }

        // 2. Filter Search / Code
        $resCode = $this->actingAs($admin)->get(route('admin.material.index', [
            'tab' => 'laporan',
            'search' => 'A001',
        ]));
        $resCode->assertStatus(200);
        $codeItems = $resCode->viewData('materials');
        $this->assertEquals(1, $codeItems->total());
        $this->assertEquals('A001', $codeItems->first()->code);

        // 3. Filter Status (Habis)
        $resHabis = $this->actingAs($admin)->get(route('admin.material.index', [
            'tab' => 'laporan',
            'status' => 'habis',
        ]));
        $resHabis->assertStatus(200);
        $habisItems = $resHabis->viewData('materials');
        foreach ($habisItems as $item) {
            $this->assertEquals(0, (float) $item->computed_stock);
        }

        // 4. Sort by code descending
        $resSort = $this->actingAs($admin)->get(route('admin.material.index', [
            'tab' => 'laporan',
            'sort' => 'code_desc',
        ]));
        $resSort->assertStatus(200);
        $sortItems = $resSort->viewData('materials');
        $firstItem = $sortItems->first();
        $this->assertEquals('A132', $firstItem->code);
    }

    public function test_import_with_month_column_does_not_set_name_to_month(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Create a fake CSV file containing Month in column 1 and Material Name in column 2
        $csvContent = "No,Bulan,Nama Barang,Satuan,Stok Awal,Masuk,Keluar,Stok Akhir\n" .
                      "1,Januari,Kabel Dropcore 1 Core,Meter,500,100,50,550\n";

        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('stok_januari.csv', $csvContent);

        $response = $this->actingAs($admin)->post(route('admin.material.import-csv'), [
            'csv_file' => $file,
        ]);
        $response->assertRedirect();

        $imported = Material::where('name', 'Kabel Dropcore 1 Core')->first();
        $this->assertNotNull($imported, "Material with real name should be imported");
        $this->assertEquals('Januari', $imported->month);
        $this->assertDatabaseMissing('materials', [
            'name' => 'Januari',
        ]);
    }

    public function test_admin_can_record_incoming_and_outgoing_stock_for_specific_month(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->seed(MaterialCsvDataSeeder::class);

        $material = Material::where('code', 'A001')->firstOrFail();
        $stockBefore = (float) $material->stock;

        // Catat Masuk di Bulan November 2026
        $resIn = $this->actingAs($admin)->post(route('admin.material.store-in'), [
            'material_id' => $material->id,
            'amount' => 50,
            'transaction_date' => '2026-11-05',
            'notes' => 'Penerimaan Batch Nov 2026',
        ]);
        $resIn->assertRedirect();

        $material->refresh();
        $this->assertEquals($stockBefore + 50, (float) $material->stock);

        // Akses view bulan November 2026
        $resNov = $this->actingAs($admin)->get(route('admin.material.index', [
            'tab' => 'masuk',
            'month' => 11,
            'year' => 2026,
        ]));
        $resNov->assertStatus(200);
        $resNov->assertSee('Penerimaan Batch Nov 2026');
        $resNov->assertSee('+50');

        // Catat Keluar di Bulan November 2026
        $resOut = $this->actingAs($admin)->post(route('admin.material.store-out'), [
            'material_id' => $material->id,
            'amount' => 20,
            'transaction_date' => '2026-11-10',
            'notes' => 'Pengeluaran Tim Fiber Nov',
        ]);
        $resOut->assertRedirect();

        $material->refresh();
        $this->assertEquals($stockBefore + 30, (float) $material->stock);

        // Akses view keluar bulan November 2026
        $resOutView = $this->actingAs($admin)->get(route('admin.material.index', [
            'tab' => 'keluar',
            'month' => 11,
            'year' => 2026,
        ]));
        $resOutView->assertStatus(200);
        $resOutView->assertSee('Pengeluaran Tim Fiber Nov');
        $resOutView->assertSee('-20');
    }

    public function test_import_single_file_with_stock_masuk_and_keluar_and_incomplete_details(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Format berkas 1 file berdampingan:
        // - Item 1: Lengkap (A001; Tiang T7; 10; 5; 2; 13)
        // - Item 2: Tanpa kode barang (keterangan tidak lengkap: ; Router ZTE F609; 15; 0; 5; 10)
        // - Item 3: Stok kosong/koma desimal (; Patchcore Biru; ; ; ; )
        // - Masuk: transaksi untuk Tiang T7 dan transaksi untuk item baru (Modem Fiberhome Baru)
        // - Keluar: transaksi untuk Tiang T7 dan Router ZTE F609
        $csvContent = implode("\n", [
            "LAPORAN STOK BARANG OKTOBER 2026;;;;;;;BARANG MASUK;;;;;Barang Keluar;;;;;;",
            ";;;;;;;;;;;;;;;;;;",
            "Kode Barang;Nama Barang;Stok Awal;Barang Masuk;Barang Keluar;Stok Akhir;;Tgl;Nama Barang;Jumlah Masuk;Catatan;;Tgl;Nama Barang;Jumlah Keluar;Catatan;;;",
            "A001;Tiang T7;10;5;2;13;;10/05/2026;Tiang T7;5;Penerimaan Gudang;;10/08/2026;Tiang T7;2;Teknisi Budi;;;",
            ";Router ZTE F609;15;0;5;10;;10/06/2026;Modem Fiberhome Baru;8;Vendor Utama;;10/09/2026;Router ZTE F609;5,5;;;;",
            ";Patchcore Biru;;;;;;;;;;;10/10/2026;Patchcore Biru;1;Aji;;;",
        ]);

        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('stok_lengkap_oktober.csv', $csvContent);

        $response = $this->actingAs($admin)->post(route('admin.material.import-csv'), [
            'csv_file' => $file,
            'import_month' => 10,
            'import_year' => 2026,
            'replace_all' => 1,
        ]);

        $response->assertRedirect(route('admin.material.index', ['month' => 10, 'year' => 2026]));
        $response->assertSessionHas('success');

        // 1. Verifikasi Item 1 (Tiang T7)
        $tiang = Material::where('code', 'A001')->first();
        $this->assertNotNull($tiang);
        $this->assertEquals('Tiang T7', $tiang->name);
        $this->assertEquals('Oktober', $tiang->month);
        $this->assertEquals(2026, $tiang->year);
        $this->assertEquals(10, (float)$tiang->initial_stock);

        // 2. Verifikasi Item 2 tanpa kode barang: otomatis diberi kode barang unik (format A...)
        $router = Material::where('name', 'Router ZTE F609')->first();
        $this->assertNotNull($router);
        $this->assertNotEmpty($router->code, "Kode barang harus digenerate otomatis jika kosong");
        $this->assertEquals('Oktober', $router->month);
        $this->assertEquals(2026, $router->year);

        // 3. Verifikasi Item 3 dengan stok kosong: otomatis terisi 0 dan terdaftar
        $patchcore = Material::where('name', 'Patchcore Biru')->first();
        $this->assertNotNull($patchcore);
        $this->assertNotEmpty($patchcore->code);
        $this->assertEquals(0, (float)$patchcore->initial_stock);

        // 4. Verifikasi item baru dari seksi Barang Masuk (Modem Fiberhome Baru): otomatis dibuat master dan mutasinya
        $modemBaru = Material::where('name', 'Modem Fiberhome Baru')->first();
        $this->assertNotNull($modemBaru, "Material baru dari Barang Masuk harus otomatis dibuat jika belum ada di master");
        $this->assertNotEmpty($modemBaru->code);
        $this->assertEquals('Oktober', $modemBaru->month);

        // 5. Verifikasi mutasi Barang Masuk tersimpan dengan tanggal periode Oktober 2026
        $mutasiMasuk = \App\Models\MaterialMutation::where('type', 'in')->get();
        $this->assertCount(2, $mutasiMasuk);
        foreach ($mutasiMasuk as $mIn) {
            $this->assertEquals('2026-10', substr($mIn->transaction_date->toDateString(), 0, 7));
        }

        // 6. Verifikasi mutasi Barang Keluar tersimpan dan angka koma desimal 5,5 terbaca sebagai 5.5
        $mutasiKeluar = \App\Models\MaterialMutation::where('type', 'out')->get();
        $this->assertCount(3, $mutasiKeluar);
        $mutasiRouterKeluar = \App\Models\MaterialMutation::where('material_id', $router->id)->where('type', 'out')->first();
        $this->assertNotNull($mutasiRouterKeluar);
        $this->assertEquals(5.5, (float)$mutasiRouterKeluar->amount);
    }

    public function test_import_actual_stok_barang_csv_file(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $csvPath = database_path('data/stok_barang.csv');
        $this->assertFileExists($csvPath);

        $uploadedFile = new \Illuminate\Http\UploadedFile(
            $csvPath,
            'stok_barang.csv',
            'text/csv',
            null,
            true
        );

        $response = $this->actingAs($admin)->post(route('admin.material.import-csv'), [
            'csv_file' => $uploadedFile,
            'replace_all' => 1,
        ]);

        $response->assertRedirect(route('admin.material.index', ['month' => 10, 'year' => 2026]));
        $response->assertSessionHas('success');

        // Verifikasi data master terimpor lengkap (lebih dari 130 item)
        $this->assertGreaterThan(130, Material::count());

        // Verifikasi transaksi masuk dan keluar dalam 1 file yang sama terbaca lengkap
        $inCount = \App\Models\MaterialMutation::where('type', 'in')->count();
        $outCount = \App\Models\MaterialMutation::where('type', 'out')->count();
        $this->assertGreaterThan(3, $inCount, "Harus membaca transaksi Barang Masuk dari file yang sama");
        $this->assertGreaterThan(20, $outCount, "Harus membaca transaksi Barang Keluar dari file yang sama");

        // Verifikasi item A001 dan A044 (DC 1C)
        $dc1c = Material::where('name', 'DC 1C')->first();
        $this->assertNotNull($dc1c);
        $this->assertEquals(2000, (float)$dc1c->initial_stock);
        $this->assertEquals(900, (float)$dc1c->total_in);
        $this->assertEquals(2485, (float)$dc1c->total_out);
        $this->assertEquals(415, (float)$dc1c->stock);

        // Verifikasi baris A35 (Modem Nokia G0425GH) yang kolom stoknya kosong di CSV tetap terimpor
        $nokia = Material::where('name', 'Modem Nokia G0425GH')->first();
        $this->assertNotNull($nokia, "Item dengan stok kosong harus tetap terbaca dan tersimpan");
        $this->assertEquals('A35', $nokia->code);
        $this->assertEquals(0, (float)$nokia->initial_stock);
        $this->assertEquals(0, (float)$nokia->stock);
    }

    public function test_importing_february_does_not_lose_or_overwrite_january_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // 1. Buat berkas CSV untuk JANUARI 2026
        $janCsv = implode("\n", [
            "LAPORAN STOK BARANG JANUARI 2026;;;;;;;BARANG MASUK;;;;;Barang Keluar;;;;;;",
            ";;;;;;;;;;;;;;;;;;",
            "Kode Barang;Nama Barang;Stok Awal;Barang Masuk;Barang Keluar;Stok Akhir;;Tgl;Nama Barang;Jumlah Masuk;Catatan;;Tgl;Nama Barang;Jumlah Keluar;Catatan;;;",
            "A001;Tiang T7;100;10;5;105;;01/10/2026;Tiang T7;10;Penerimaan Januari;;01/15/2026;Tiang T7;5;Pengeluaran Januari;;;",
            "A002;Tiang T6;50;20;10;60;;;;;;;;;;;;;",
        ]);

        $fileJan = \Illuminate\Http\UploadedFile::fake()->createWithContent('stok_januari.csv', $janCsv);

        $resJan = $this->actingAs($admin)->post(route('admin.material.import-csv'), [
            'csv_file' => $fileJan,
            'import_month' => 1,
            'import_year' => 2026,
        ]);
        $resJan->assertRedirect();
        $resJan->assertSessionHas('success');

        // Pastikan data Januari terbaca di index
        $viewJan = $this->actingAs($admin)->get(route('admin.material.index', ['month' => 1, 'year' => 2026]));
        $viewJan->assertStatus(200);
        $viewJan->assertSee('Tiang T7');
        $viewJan->assertSee('Tiang T6');
        $materialsJan = $viewJan->viewData('materials');
        $this->assertCount(2, $materialsJan);
        $item1Jan = $materialsJan->firstWhere('code', 'A001');
        $this->assertEquals(100, (float)$item1Jan->computed_initial_stock);
        $this->assertEquals(10, (float)$item1Jan->computed_total_in);
        $this->assertEquals(5, (float)$item1Jan->computed_total_out);
        $this->assertEquals(105, (float)$item1Jan->computed_stock);

        // 2. Sekarang impor berkas CSV untuk FEBRUARI 2026
        $febCsv = implode("\n", [
            "LAPORAN STOK BARANG FEBRUARI 2026;;;;;;;BARANG MASUK;;;;;Barang Keluar;;;;;;",
            ";;;;;;;;;;;;;;;;;;",
            "Kode Barang;Nama Barang;Stok Awal;Barang Masuk;Barang Keluar;Stok Akhir;;Tgl;Nama Barang;Jumlah Masuk;Catatan;;Tgl;Nama Barang;Jumlah Keluar;Catatan;;;",
            "A001;Tiang T7;105;15;8;112;;02/08/2026;Tiang T7;15;Penerimaan Februari;;02/12/2026;Tiang T7;8;Pengeluaran Februari;;;",
            "A002;Tiang T6;60;5;2;63;;;;;;;;;;;;;",
            "A003;Modem ONT ZTE;30;10;5;35;;;;;;;;;;;;;",
        ]);

        $fileFeb = \Illuminate\Http\UploadedFile::fake()->createWithContent('stok_februari.csv', $febCsv);

        $resFeb = $this->actingAs($admin)->post(route('admin.material.import-csv'), [
            'csv_file' => $fileFeb,
            'import_month' => 2,
            'import_year' => 2026,
        ]);
        $resFeb->assertRedirect();
        $resFeb->assertSessionHas('success');

        // 3. Verifikasi Tampilan FEBRUARI 2026
        $viewFeb = $this->actingAs($admin)->get(route('admin.material.index', ['month' => 2, 'year' => 2026]));
        $viewFeb->assertStatus(200);
        $viewFeb->assertSee('Tiang T7');
        $viewFeb->assertSee('Tiang T6');
        $viewFeb->assertSee('Modem ONT ZTE');
        $materialsFeb = $viewFeb->viewData('materials');
        $this->assertCount(3, $materialsFeb);

        $item1Feb = $materialsFeb->firstWhere('code', 'A001');
        $this->assertEquals(105, (float)$item1Feb->computed_initial_stock);
        $this->assertEquals(15, (float)$item1Feb->computed_total_in);
        $this->assertEquals(8, (float)$item1Feb->computed_total_out);
        $this->assertEquals(112, (float)$item1Feb->computed_stock);

        // 4. CRITICAL ASSERTION: Pastikan data JANUARI TIDAK HILANG!
        $viewJanAgain = $this->actingAs($admin)->get(route('admin.material.index', ['month' => 1, 'year' => 2026]));
        $viewJanAgain->assertStatus(200);
        $viewJanAgain->assertSee('Tiang T7');
        $viewJanAgain->assertSee('Tiang T6');
        $materialsJanAgain = $viewJanAgain->viewData('materials');
        $this->assertCount(2, $materialsJanAgain, "Data Januari harus tetap ada 2 item setelah Februari diimpor");

        $item1JanAgain = $materialsJanAgain->firstWhere('code', 'A001');
        $this->assertNotNull($item1JanAgain, "Item A001 di bulan Januari tidak boleh hilang");
        $this->assertEquals(100, (float)$item1JanAgain->computed_initial_stock, "Stok awal Januari tidak boleh tertimpa nilai Februari");
        $this->assertEquals(10, (float)$item1JanAgain->computed_total_in, "Barang masuk Januari tidak boleh tertimpa nilai Februari");
        $this->assertEquals(5, (float)$item1JanAgain->computed_total_out, "Barang keluar Januari tidak boleh tertimpa nilai Februari");
        $this->assertEquals(105, (float)$item1JanAgain->computed_stock, "Stok akhir Januari tidak boleh tertimpa nilai Februari");

        // 5. Verifikasi Tab Barang Masuk & Keluar per Bulan
        $inJan = $this->actingAs($admin)->get(route('admin.material.index', ['tab' => 'masuk', 'month' => 1, 'year' => 2026]));
        $inJan->assertSee('Penerimaan Januari');
        $inJan->assertDontSee('Penerimaan Februari');

        $inFeb = $this->actingAs($admin)->get(route('admin.material.index', ['tab' => 'masuk', 'month' => 2, 'year' => 2026]));
        $inFeb->assertSee('Penerimaan Februari');
        $inFeb->assertDontSee('Penerimaan Januari');

        // 6. Test jika user centang 'replace_all' saat mengimpor bulan Februari lagi:
        //    Data Januari tetap tidak boleh terhapus!
        $resFebReplace = $this->actingAs($admin)->post(route('admin.material.import-csv'), [
            'csv_file' => $fileFeb,
            'import_month' => 2,
            'import_year' => 2026,
            'replace_all' => 1,
        ]);
        $resFebReplace->assertRedirect();

        // Data Januari tetap utuh dan lengkap!
        $viewJanFinal = $this->actingAs($admin)->get(route('admin.material.index', ['month' => 1, 'year' => 2026]));
        $this->assertCount(2, $viewJanFinal->viewData('materials'), "Data Januari tetap harus utuh meskipun Februari di-replace");
    }

    public function test_admin_and_direktur_view_all_reports_and_mutations_when_filtering_semua_periode(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $direktur = User::factory()->create(['role' => 'direktur']);

        $mat1 = Material::create([
            'code' => 'T901',
            'name' => 'Kabel Drop Core Jan',
            'category' => 'Kabel Drop & Fiber Optik',
            'unit' => 'Meter',
            'initial_stock' => 100,
            'total_in' => 20,
            'total_out' => 10,
            'stock' => 110,
            'month' => 'Januari',
            'year' => 2026,
        ]);

        \App\Models\MaterialMonthlyStock::create([
            'material_id' => $mat1->id,
            'month' => 1,
            'month_name' => 'Januari',
            'year' => 2026,
            'initial_stock' => 100,
            'total_in' => 20,
            'total_out' => 10,
            'final_stock' => 110,
        ]);

        $mat2 = Material::create([
            'code' => 'T902',
            'name' => 'Kabel Drop Core Feb',
            'category' => 'Kabel Drop & Fiber Optik',
            'unit' => 'Meter',
            'initial_stock' => 200,
            'total_in' => 50,
            'total_out' => 30,
            'stock' => 220,
            'month' => 'Februari',
            'year' => 2026,
        ]);

        \App\Models\MaterialMonthlyStock::create([
            'material_id' => $mat2->id,
            'month' => 2,
            'month_name' => 'Februari',
            'year' => 2026,
            'initial_stock' => 200,
            'total_in' => 50,
            'total_out' => 30,
            'final_stock' => 220,
        ]);

        \App\Models\MaterialMutation::create([
            'material_id' => $mat1->id,
            'user_id' => $admin->id,
            'type' => 'in',
            'transaction_date' => '2026-01-10',
            'amount' => 20,
            'notes' => 'Penerimaan Drop Core Januari',
        ]);

        \App\Models\MaterialMutation::create([
            'material_id' => $mat1->id,
            'user_id' => $admin->id,
            'type' => 'out',
            'transaction_date' => '2026-01-15',
            'amount' => 10,
            'notes' => 'Pengeluaran Drop Core Januari',
        ]);

        \App\Models\MaterialMutation::create([
            'material_id' => $mat2->id,
            'user_id' => $admin->id,
            'type' => 'in',
            'transaction_date' => '2026-02-10',
            'amount' => 50,
            'notes' => 'Penerimaan Drop Core Februari',
        ]);

        \App\Models\MaterialMutation::create([
            'material_id' => $mat2->id,
            'user_id' => $admin->id,
            'type' => 'out',
            'transaction_date' => '2026-02-15',
            'amount' => 30,
            'notes' => 'Pengeluaran Drop Core Februari',
        ]);

        foreach ([$admin, $direktur] as $roleUser) {
            // 1. Tab Laporan Stok Barang dengan filter Semua Periode (month=all)
            $response = $this->actingAs($roleUser)->get(route('admin.material.index', ['month' => 'all']));
            $response->assertOk();
            $response->assertSee('Kabel Drop Core Jan');
            $response->assertSee('Kabel Drop Core Feb');
            $response->assertSee('Januari');
            $response->assertSee('Februari');

            // 2. Tab Barang Masuk dengan filter Semua Periode (month=all)
            $resIn = $this->actingAs($roleUser)->get(route('admin.material.index', ['tab' => 'masuk', 'month' => 'all']));
            $resIn->assertOk();
            $resIn->assertSee('Penerimaan Drop Core Januari');
            $resIn->assertSee('Penerimaan Drop Core Februari');

            // 3. Tab Barang Keluar dengan filter Semua Periode (month=all)
            $resOut = $this->actingAs($roleUser)->get(route('admin.material.index', ['tab' => 'keluar', 'month' => 'all']));
            $resOut->assertOk();
            $resOut->assertSee('Pengeluaran Drop Core Januari');
            $resOut->assertSee('Pengeluaran Drop Core Februari');
        }
    }

    /**
     * Menguji bahwa sistem membaca data Barang Masuk dan Barang Keluar secara akurat
     * dengan aturan: Tanggal (urutan tanggal-bulan-tahun) -> Nama Barang -> Jumlah -> Catatan.
     */
    public function test_system_reads_incoming_and_outgoing_tables_accurately_with_dmy_order_and_zero_data_loss(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Data berkas CSV dengan format urutan 4 kolom:
        // Masuk: [Tanggal, Nama Barang, Jumlah Masuk, Catatan]
        // Keluar: [Tanggal, Nama Barang, Jumlah Keluar, Catatan]
        $csvData = implode("\n", [
            "LAPORAN STOK BARANG (OKTOBER 2026);;;;;;;BARANG MASUK;;;;;Barang Keluar;;;;;;",
            ";;;;;;;;;;;;;;;;;;",
            "Tgl Stok Awal;01/10/2026;;;;;;;;;;;;;;;",
            "Tgl Closing;31/10/2026;;;;;;;;;;;;;;;",
            ";;;;;;;;;;;;;;;;;;",
            "Kode Barang;Nama Barang;Stok Awal;Barang Masuk;Barang Keluar;Stok Akhir;;Tgl;Nama Barang;Jumlah Masuk;Catatan;;Tgl;Nama Barang;Jumlah Keluar;Catatan;;;",
            "M001;Kabel FO 24 Core;200;150;45;305;;19/10/2026;Kabel FO 24 Core;150;Pengadaan Proyek Baru;;22/10/2026;Kabel FO 24 Core;45;Pemasangan Jalur Utama;;;",
            "M002;SFP Transceiver 10G;50;20;10;60;;25-10-2026;SFP Transceiver 10G;20;Dari Vendor Huawei;;28-10-2026;SFP Transceiver 10G;10;Penggantian Core Switch;;;",
            ";;;;;;;;;;;;;;;;;;",
            "TOTAL;TOTAL STOK;250;170;55;365;;TOTAL MASUK;;170;;;TOTAL KELUAR;;55;;;;",
        ]);

        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('stok_oktober_aturan_baru.csv', $csvData);

        $response = $this->actingAs($admin)->post(route('admin.material.import-csv'), [
            'csv_file' => $file,
            'import_month' => 10,
            'import_year' => 2026,
            'replace_all' => 1,
        ]);

        $response->assertRedirect(route('admin.material.index', ['month' => 10, 'year' => 2026]));
        $response->assertSessionHas('success');

        // 1. Verifikasi data master material tersimpan akurat dan tidak ada baris 'TOTAL' yang dibuat sebagai material
        $mat1 = Material::where('code', 'M001')->first();
        $this->assertNotNull($mat1);
        $this->assertEquals('Kabel FO 24 Core', $mat1->name);
        $this->assertEquals(200, (float)$mat1->initial_stock);
        $this->assertEquals(150, (float)$mat1->total_in);
        $this->assertEquals(45, (float)$mat1->total_out);
        $this->assertEquals(305, (float)$mat1->stock);

        $this->assertNull(Material::where('name', 'like', '%TOTAL%')->first(), "Baris ringkasan TOTAL tidak boleh disimpan sebagai material master");

        // 2. Verifikasi aturan 1: Barang Masuk dibaca mulai Tanggal (tanggal-bulan-tahun) -> Nama Barang -> Jumlah Masuk -> Catatan
        $inMutations = \App\Models\MaterialMutation::where('type', 'in')->orderBy('id', 'asc')->get();
        $this->assertCount(2, $inMutations);

        $in1 = $inMutations->firstWhere('amount', 150);
        $this->assertNotNull($in1);
        $this->assertEquals('2026-10-19', $in1->transaction_date->toDateString(), "Urutan tanggal-bulan-tahun 19/10/2026 harus terbaca 2026-10-19");
        $this->assertEquals($mat1->id, $in1->material_id);
        $this->assertEquals('Pengadaan Proyek Baru', $in1->notes, "Catatan masuk harus tersimpan persis tanpa deformasi");

        $in2 = $inMutations->firstWhere('amount', 20);
        $this->assertNotNull($in2);
        $this->assertEquals('2026-10-25', $in2->transaction_date->toDateString(), "Urutan tanggal-bulan-tahun 25-10-2026 harus terbaca 2026-10-25");
        $this->assertEquals('Dari Vendor Huawei', $in2->notes);

        // 3. Verifikasi aturan 2: Barang Keluar dibaca mulai Tanggal (tanggal-bulan-tahun) -> Nama Barang -> Jumlah Keluar -> Catatan
        $outMutations = \App\Models\MaterialMutation::where('type', 'out')->orderBy('id', 'asc')->get();
        $this->assertCount(2, $outMutations);

        $out1 = $outMutations->firstWhere('amount', 45);
        $this->assertNotNull($out1);
        $this->assertEquals('2026-10-22', $out1->transaction_date->toDateString(), "Urutan tanggal-bulan-tahun 22/10/2026 harus terbaca 2026-10-22");
        $this->assertEquals($mat1->id, $out1->material_id);
        $this->assertEquals('Pemasangan Jalur Utama', $out1->notes, "Catatan keluar harus tersimpan persis tanpa deformasi");

        $out2 = $outMutations->firstWhere('amount', 10);
        $this->assertNotNull($out2);
        $this->assertEquals('2026-10-28', $out2->transaction_date->toDateString(), "Urutan tanggal-bulan-tahun 28-10-2026 harus terbaca 2026-10-28");
        $this->assertEquals('Penggantian Core Switch', $out2->notes);
    }

    /**
     * Menguji ekspor dan re-impor berkas Excel (.xls) dan CSV (.csv) roundtrip
     * dengan jaminan semua data dan catatan terbaca tanpa ada yang tertinggal.
     */
    public function test_roundtrip_export_and_import_excel_and_csv_retains_all_data_accurately(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $mat = Material::create([
            'code' => 'T001',
            'name' => 'Patchcore Kuning SC-UPC',
            'category' => 'Patchcore',
            'unit' => 'Pcs',
            'month' => 'Oktober',
            'year' => 2026,
            'initial_stock' => 100,
            'total_in' => 50,
            'total_out' => 20,
            'stock' => 130,
            'min_stock' => 10,
        ]);

        \App\Models\MaterialMonthlyStock::create([
            'material_id' => $mat->id,
            'month' => 10,
            'year' => 2026,
            'month_name' => 'Oktober',
            'initial_stock' => 100,
            'total_in' => 50,
            'total_out' => 20,
            'final_stock' => 130,
        ]);

        \App\Models\MaterialMutation::create([
            'material_id' => $mat->id,
            'user_id' => $admin->id,
            'type' => 'in',
            'transaction_date' => '2026-10-15',
            'amount' => 50,
            'stock_before' => 100,
            'stock_after' => 150,
            'notes' => 'Penerimaan Pabrik Pusat',
        ]);

        \App\Models\MaterialMutation::create([
            'material_id' => $mat->id,
            'user_id' => $admin->id,
            'type' => 'out',
            'transaction_date' => '2026-10-20',
            'amount' => 20,
            'stock_before' => 150,
            'stock_after' => 130,
            'notes' => 'Instalasi Pelanggan VIP',
        ]);

        // 1. Uji Ekspor CSV dan Impor Kembali
        $csvResponse = $this->actingAs($admin)->get(route('admin.material.export-csv', ['month' => 10, 'year' => 2026]));
        $csvResponse->assertOk();
        $csvContent = $csvResponse->streamedContent();

        $this->assertStringContainsString('Patchcore Kuning SC-UPC', $csvContent);
        $this->assertStringContainsString('Penerimaan Pabrik Pusat', $csvContent);
        $this->assertStringContainsString('Instalasi Pelanggan VIP', $csvContent);
        $this->assertStringContainsString('15/10/2026', $csvContent, "Format tanggal dalam ekspor CSV harus d/m/Y (tanggal-bulan-tahun)");
        $this->assertStringContainsString('20/10/2026', $csvContent, "Format tanggal dalam ekspor CSV harus d/m/Y (tanggal-bulan-tahun)");

        // Re-impor file CSV yang diekspor
        $reimportCsvFile = \Illuminate\Http\UploadedFile::fake()->createWithContent('reimport_oktober.csv', $csvContent);
        $reimportCsvRes = $this->actingAs($admin)->post(route('admin.material.import-csv'), [
            'csv_file' => $reimportCsvFile,
            'import_month' => 10,
            'import_year' => 2026,
            'replace_all' => 1,
        ]);
        $reimportCsvRes->assertRedirect();
        $reimportCsvRes->assertSessionHas('success');

        $matAfterCsv = Material::where('code', 'T001')->first();
        $this->assertEquals(130, (float)$matAfterCsv->stock);
        $this->assertEquals(50, (float)$matAfterCsv->total_in);
        $this->assertEquals(20, (float)$matAfterCsv->total_out);

        // 2. Uji Ekspor Excel (.xls) dan Impor Kembali
        $excelResponse = $this->actingAs($admin)->get(route('admin.material.export', ['month' => 10, 'year' => 2026]));
        $excelResponse->assertOk();
        $excelContent = $excelResponse->streamedContent();

        $this->assertStringContainsString('Patchcore Kuning SC-UPC', $excelContent);
        $this->assertStringContainsString('Penerimaan Pabrik Pusat', $excelContent);
        $this->assertStringContainsString('Instalasi Pelanggan VIP', $excelContent);
        $this->assertStringContainsString('15/10/2026', $excelContent);
        $this->assertStringContainsString('20/10/2026', $excelContent);

        // Re-impor file Excel (.xls) yang diekspor
        $reimportExcelFile = \Illuminate\Http\UploadedFile::fake()->createWithContent('reimport_oktober.xls', $excelContent);
        $reimportExcelRes = $this->actingAs($admin)->post(route('admin.material.import-csv'), [
            'csv_file' => $reimportExcelFile,
            'import_month' => 10,
            'import_year' => 2026,
            'replace_all' => 1,
        ]);
        $reimportExcelRes->assertRedirect();
        $reimportExcelRes->assertSessionHas('success');

        $matAfterExcel = Material::where('code', 'T001')->first();
        $this->assertEquals(130, (float)$matAfterExcel->stock);
        $this->assertEquals(50, (float)$matAfterExcel->total_in);
        $this->assertEquals(20, (float)$matAfterExcel->total_out);

        $mutationInAfter = \App\Models\MaterialMutation::where('type', 'in')->first();
        $this->assertNotNull($mutationInAfter);
        $this->assertEquals('2026-10-15', $mutationInAfter->transaction_date->toDateString());
        $this->assertEquals('Penerimaan Pabrik Pusat', $mutationInAfter->notes);

        $mutationOutAfter = \App\Models\MaterialMutation::where('type', 'out')->first();
        $this->assertNotNull($mutationOutAfter);
        $this->assertEquals('2026-10-20', $mutationOutAfter->transaction_date->toDateString());
        $this->assertEquals('Instalasi Pelanggan VIP', $mutationOutAfter->notes);
    }
}




