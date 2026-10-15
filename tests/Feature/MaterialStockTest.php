<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\MaterialMutation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialStockTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $direktur;
    protected User $technician;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@banterpool.net',
            'name' => 'Administrator NOC',
        ]);

        $this->direktur = User::factory()->create([
            'role' => 'direktur',
            'email' => 'direktur@banterpool.net',
            'name' => 'Direktur Utama',
        ]);

        $this->technician = User::factory()->create([
            'role' => 'technician',
            'email' => 'teknisi@banterpool.net',
        ]);

        $this->customer = User::factory()->create([
            'role' => 'customer',
            'email' => 'pelanggan@example.com',
        ]);
    }

    public function test_sidebar_displays_stok_material_menu_for_admin_and_direktur(): void
    {
        // 1. Verifikasi POV Admin
        $responseAdmin = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $responseAdmin->assertOk();
        $responseAdmin->assertSee('Stok Barang');
        $responseAdmin->assertSee(route('admin.material.index'));

        // 2. Verifikasi POV Direktur
        $responseDirektur = $this->actingAs($this->direktur)->get(route('admin.dashboard'));
        $responseDirektur->assertOk();
        $responseDirektur->assertSee('Stok Barang');
        $responseDirektur->assertSee(route('admin.material.index'));
        $responseDirektur->assertSee('Direktur Utama');
    }

    public function test_both_admin_and_direktur_can_access_stok_material_page(): void
    {
        $material = Material::create([
            'code' => 'MAT-ONT-999',
            'name' => 'Modem ONT ZTE F609 V3',
            'category' => 'Modem & ONT',
            'unit' => 'Unit',
            'stock' => 20,
            'min_stock' => 5,
            'unit_price' => 185000,
            'location' => 'Gudang NOC Rak A1',
            'description' => 'ONT GPON Gigabit',
        ]);

        // 1. POV Admin
        $resAdmin = $this->actingAs($this->admin)->get(route('admin.material.index'));
        $resAdmin->assertOk();
        $resAdmin->assertSee('Manajemen Stok Barang & Perangkat', false);
        $resAdmin->assertSee('Modem ONT ZTE F609 V3');
        $resAdmin->assertSee('MAT-ONT-999');

        // 2. POV Direktur
        $resDirektur = $this->actingAs($this->direktur)->get(route('admin.material.index'));
        $resDirektur->assertOk();
        $resDirektur->assertSee('Manajemen Stok Barang & Perangkat', false);
        $resDirektur->assertSee('Modem ONT ZTE F609 V3');
        $resDirektur->assertSee('MAT-ONT-999');
    }

    public function test_non_admin_cannot_access_stok_material(): void
    {
        // Guest
        $resGuest = $this->get(route('admin.material.index'));
        $resGuest->assertRedirect(route('login'));

        // Customer (403 Forbidden)
        $resCustomer = $this->actingAs($this->customer)->get(route('admin.material.index'));
        $resCustomer->assertForbidden();

        // Technician (403 Forbidden)
        $resTech = $this->actingAs($this->technician)->get(route('admin.material.index'));
        $resTech->assertForbidden();
    }

    public function test_admin_and_direktur_can_create_new_material(): void
    {
        // Admin membuat material
        $responseAdmin = $this->actingAs($this->admin)->post(route('admin.material.store'), [
            'code' => 'MAT-KBL-100',
            'name' => 'Kabel Drop Core 1 Core 1000m',
            'category' => 'Kabel & Fiber',
            'unit' => 'Roll',
            'stock' => 10,
            'min_stock' => 3,
            'unit_price' => 485000,
            'location' => 'Gudang Utama Palet B',
            'description' => 'Drop core outdoor 3 seling kawat',
        ]);

        $responseAdmin->assertRedirect();
        $responseAdmin->assertSessionHas('success');

        $this->assertDatabaseHas('materials', [
            'code' => 'MAT-KBL-100',
            'name' => 'Kabel Drop Core 1 Core 1000m',
            'stock' => 10,
        ]);

        // Mutasi awal tercatat
        $material = Material::where('code', 'MAT-KBL-100')->first();
        $this->assertEquals(1, $material->mutations()->count());
        $this->assertEquals('in', $material->mutations()->first()->type);
        $this->assertEquals(10, $material->mutations()->first()->amount);

        // Direktur membuat material
        $responseDirektur = $this->actingAs($this->direktur)->post(route('admin.material.store'), [
            'code' => 'MAT-FC-050',
            'name' => 'Fast Connector SC/UPC',
            'category' => 'Konektor & Aksesoris',
            'unit' => 'Box',
            'stock' => 50,
            'min_stock' => 10,
            'unit_price' => 135000,
            'location' => 'Gudang NOC Rak B1',
        ]);

        $responseDirektur->assertRedirect();
        $responseDirektur->assertSessionHas('success');

        $this->assertDatabaseHas('materials', [
            'code' => 'MAT-FC-050',
            'name' => 'Fast Connector SC/UPC',
            'stock' => 50,
        ]);
    }

    public function test_manual_stock_addition_increments_stock_and_logs_mutation(): void
    {
        $material = Material::create([
            'code' => 'MAT-TEST-01',
            'name' => 'Patch Cord SC/UPC 3m',
            'category' => 'Konektor & Aksesoris',
            'unit' => 'Pcs',
            'stock' => 15,
            'min_stock' => 5,
        ]);

        // POV Direktur melakukan penambahan manual +20
        $response = $this->actingAs($this->direktur)->post(route('admin.material.add-stock', $material->id), [
            'amount' => 20,
            'notes' => 'Pengadaan baru dari distributor PT Telekom',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $material->refresh();
        $this->assertEquals(35, $material->stock);

        // Cek log mutasi
        $this->assertDatabaseHas('material_mutations', [
            'material_id' => $material->id,
            'user_id' => $this->direktur->id,
            'type' => 'in',
            'amount' => 20,
            'stock_before' => 15,
            'stock_after' => 35,
            'notes' => 'Pengadaan baru dari distributor PT Telekom',
        ]);
    }

    public function test_manual_stock_reduction_decrements_stock_and_logs_mutation(): void
    {
        $material = Material::create([
            'code' => 'MAT-TEST-02',
            'name' => 'Roset Optik 1 Core',
            'category' => 'Konektor & Aksesoris',
            'unit' => 'Pcs',
            'stock' => 40,
            'min_stock' => 10,
        ]);

        // POV Admin melakukan pengurangan manual -12
        $response = $this->actingAs($this->admin)->post(route('admin.material.reduce-stock', $material->id), [
            'amount' => 12,
            'notes' => 'Pemakaian instalasi cluster Desa Purwokerto',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $material->refresh();
        $this->assertEquals(28, $material->stock);

        // Cek log mutasi
        $this->assertDatabaseHas('material_mutations', [
            'material_id' => $material->id,
            'user_id' => $this->admin->id,
            'type' => 'out',
            'amount' => 12,
            'stock_before' => 40,
            'stock_after' => 28,
            'notes' => 'Pemakaian instalasi cluster Desa Purwokerto',
        ]);
    }

    public function test_cannot_reduce_stock_more_than_available(): void
    {
        $material = Material::create([
            'code' => 'MAT-TEST-03',
            'name' => 'Splitter PLC 1:8',
            'category' => 'Pasif & ODP',
            'unit' => 'Pcs',
            'stock' => 5,
            'min_stock' => 2,
        ]);

        // Coba kurangi 10 (lebih besar dari stok 5)
        $response = $this->actingAs($this->admin)->post(route('admin.material.reduce-stock', $material->id), [
            'amount' => 10,
            'notes' => 'Coba kurangi melebihi stok',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $material->refresh();
        $this->assertEquals(5, $material->stock); // Stok tidak berubah
    }

    public function test_manual_stock_adjustment_updates_stock_and_logs_adjustment(): void
    {
        $material = Material::create([
            'code' => 'MAT-TEST-04',
            'name' => 'ODP Tiang 8 Core',
            'category' => 'Pasif & ODP',
            'unit' => 'Unit',
            'stock' => 10,
            'min_stock' => 3,
        ]);

        // Penyesuaian opname langsung jadi 14 oleh Direktur
        $response = $this->actingAs($this->direktur)->post(route('admin.material.adjust-stock', $material->id), [
            'new_stock' => 14,
            'notes' => 'Hasil stock opname fisik gudang mingguan',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $material->refresh();
        $this->assertEquals(14, $material->stock);

        $this->assertDatabaseHas('material_mutations', [
            'material_id' => $material->id,
            'user_id' => $this->direktur->id,
            'type' => 'adjustment',
            'amount' => 4,
            'stock_before' => 10,
            'stock_after' => 14,
        ]);
    }

    public function test_manual_delete_single_material(): void
    {
        $material = Material::create([
            'code' => 'MAT-DEL-01',
            'name' => 'Material Yang Mau Dihapus',
            'category' => 'Umum',
            'unit' => 'Pcs',
            'stock' => 0,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.material.destroy', $material->id));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('materials', [
            'id' => $material->id,
        ]);
    }

    public function test_manual_bulk_delete_materials(): void
    {
        $m1 = Material::create(['code' => 'MAT-B1', 'name' => 'Barang B1', 'category' => 'Umum', 'unit' => 'Pcs', 'stock' => 0]);
        $m2 = Material::create(['code' => 'MAT-B2', 'name' => 'Barang B2', 'category' => 'Umum', 'unit' => 'Pcs', 'stock' => 0]);

        $response = $this->actingAs($this->direktur)->post(route('admin.material.bulk-delete'), [
            'ids' => [$m1->id, $m2->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('materials', ['id' => $m1->id]);
        $this->assertDatabaseMissing('materials', ['id' => $m2->id]);
    }

    public function test_admin_and_direktur_can_delete_all_materials(): void
    {
        Material::create(['code' => 'MAT-ALL1', 'name' => 'All Item 1', 'category' => 'Umum', 'unit' => 'Pcs', 'stock' => 10]);
        Material::create(['code' => 'MAT-ALL2', 'name' => 'All Item 2', 'category' => 'Umum', 'unit' => 'Pcs', 'stock' => 5]);

        $this->assertEquals(2, Material::count());

        $response = $this->actingAs($this->direktur)->post(route('admin.material.bulk-delete'), [
            'delete_all' => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals(0, Material::count());
        $this->assertEquals(0, MaterialMutation::count());
    }

    public function test_export_material_excel_downloads_successfully(): void
    {
        Material::create([
            'code' => 'MAT-EXP-01',
            'name' => 'Barang Export Test',
            'category' => 'Perangkat',
            'unit' => 'Unit',
            'stock' => 10,
            'min_stock' => 2,
            'unit_price' => 200000,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.material.export'));
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.ms-excel; charset=UTF-8');
        $this->assertStringContainsString('Barang Export Test', $response->streamedContent());
        $this->assertStringContainsString('MAT-EXP-01', $response->streamedContent());
    }

    public function test_mutations_audit_log_page_renders_accurately(): void
    {
        $material = Material::create([
            'code' => 'MAT-MUT-LOG',
            'name' => 'Barang Log Mutasi',
            'category' => 'Modem',
            'unit' => 'Unit',
            'stock' => 5,
        ]);

        $material->mutations()->create([
            'user_id' => $this->admin->id,
            'type' => 'in',
            'amount' => 5,
            'stock_before' => 0,
            'stock_after' => 5,
            'notes' => 'Catatan uji log mutasi',
        ]);

        $response = $this->actingAs($this->direktur)->get(route('admin.material.mutations'));
        $response->assertOk();
        $response->assertSee('Barang Keluar & Masuk', false);
        $response->assertSee('Barang Log Mutasi');
        $response->assertSee('Catatan uji log mutasi');
    }
}
