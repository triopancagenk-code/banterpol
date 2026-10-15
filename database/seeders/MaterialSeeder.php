<?php

namespace Database\Seeders;

use App\Models\Material;
use App\Models\User;
use Illuminate\Database\Seeder;

class MaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', 'admin')->orWhere('email', 'like', '%admin%')->first();
        $adminId = $admin ? $admin->id : null;

        $materials = [
            [
                'code' => 'MAT-ONT-001',
                'name' => 'Modem ONT ZTE F609 V3 (Gigabit Dual Band)',
                'category' => 'Modem & ONT',
                'unit' => 'Unit',
                'stock' => 45,
                'min_stock' => 10,
                'unit_price' => 185000,
                'location' => 'Gudang NOC - Rak A1',
                'description' => 'ONT GPON 4 GE port + 2 FXS + 1 USB + Wi-Fi 2.4GHz untuk pemasangan pelanggan baru.',
            ],
            [
                'code' => 'MAT-ONT-002',
                'name' => 'Modem ONT Huawei HG8245H5 (Single Band)',
                'category' => 'Modem & ONT',
                'unit' => 'Unit',
                'stock' => 28,
                'min_stock' => 8,
                'unit_price' => 170000,
                'location' => 'Gudang NOC - Rak A2',
                'description' => 'ONT GPON 4 FE/GE + Wi-Fi b/g/n standar pelanggan residensial.',
            ],
            [
                'code' => 'MAT-KBL-001',
                'name' => 'Kabel Drop Core Fiber Optik 1 Core 3 Steel Wire (1000m)',
                'category' => 'Kabel & Fiber',
                'unit' => 'Roll',
                'stock' => 12,
                'min_stock' => 4,
                'unit_price' => 485000,
                'location' => 'Gudang Utama - Palet B',
                'description' => 'Drop cable outdoor 1 core G657A1 dengan messenger kawat baja 3 seling.',
            ],
            [
                'code' => 'MAT-KBL-002',
                'name' => 'Kabel Drop Core Fiber Optik 2 Core (1000m)',
                'category' => 'Kabel & Fiber',
                'unit' => 'Roll',
                'stock' => 6,
                'min_stock' => 3,
                'unit_price' => 620000,
                'location' => 'Gudang Utama - Palet B',
                'description' => 'Drop core outdoor 2 core untuk jalur distribusi ODP cadangan.',
            ],
            [
                'code' => 'MAT-FC-001',
                'name' => 'Fast Connector SC/UPC Biru (Isi 100 pcs)',
                'category' => 'Konektor & Aksesoris',
                'unit' => 'Box',
                'stock' => 15,
                'min_stock' => 5,
                'unit_price' => 135000,
                'location' => 'Gudang NOC - Rak B1',
                'description' => 'Fast connector SC/UPC manual cold joint tanpa splicing untuk terminasi roset.',
            ],
            [
                'code' => 'MAT-FC-002',
                'name' => 'Fast Connector SC/APC Hijau (Isi 100 pcs)',
                'category' => 'Konektor & Aksesoris',
                'unit' => 'Box',
                'stock' => 8,
                'min_stock' => 3,
                'unit_price' => 145000,
                'location' => 'Gudang NOC - Rak B1',
                'description' => 'Fast connector SC/APC untuk terminasi ODP/ODC.',
            ],
            [
                'code' => 'MAT-PC-001',
                'name' => 'Patch Cord SC/UPC - SC/UPC Simplex 3 Meter',
                'category' => 'Konektor & Aksesoris',
                'unit' => 'Pcs',
                'stock' => 65,
                'min_stock' => 15,
                'unit_price' => 12500,
                'location' => 'Gudang NOC - Rak B2',
                'description' => 'Patch cord kuning single mode 3m penghubung Roset ke Modem ONT.',
            ],
            [
                'code' => 'MAT-RST-001',
                'name' => 'Roset Optik 1 Core Wall Mount Box',
                'category' => 'Konektor & Aksesoris',
                'unit' => 'Pcs',
                'stock' => 80,
                'min_stock' => 20,
                'unit_price' => 5500,
                'location' => 'Gudang NOC - Rak B3',
                'description' => 'Roset kotak terminasi dinding indoor 1 adapter SC.',
            ],
            [
                'code' => 'MAT-SPL-001',
                'name' => 'Splitter PLC 1:8 Mini Tube Tanpa Konektor',
                'category' => 'Pasif & ODP',
                'unit' => 'Pcs',
                'stock' => 24,
                'min_stock' => 6,
                'unit_price' => 45000,
                'location' => 'Gudang NOC - Rak C1',
                'description' => 'PLC Optical Splitter 1 ke 8 steel tube untuk modul ODP 8 port.',
            ],
            [
                'code' => 'MAT-ODP-001',
                'name' => 'Optical Distribution Point (ODP) Tiang 8 Core',
                'category' => 'Pasif & ODP',
                'unit' => 'Unit',
                'stock' => 7,
                'min_stock' => 5,
                'unit_price' => 120000,
                'location' => 'Gudang Utama - Rak C2',
                'description' => 'Box ODP solid outdoor waterproof lengkap klem tiang dan adapter tray.',
            ],
            [
                'code' => 'MAT-SFP-001',
                'name' => 'SFP GPON OLT Class C++ 20km (Tx 1490nm / Rx 1310nm)',
                'category' => 'Perangkat Jaringan',
                'unit' => 'Pcs',
                'stock' => 4,
                'min_stock' => 2,
                'unit_price' => 420000,
                'location' => 'Ruang Server NOC - Brankas',
                'description' => 'Transceiver optik uplink GPON OLT ZTE/Huawei.',
            ],
            [
                'code' => 'MAT-TLS-001',
                'name' => 'Optical Power Meter (OPM) & Visual Fault Locator (VFL)',
                'category' => 'Tools & Alat Kerja',
                'unit' => 'Set',
                'stock' => 5,
                'min_stock' => 2,
                'unit_price' => 250000,
                'location' => 'Ruang Teknisi - Loker 01',
                'description' => 'Alat ukur redaman dBm serat optik dan laser senter pendeteksi putus kabel.',
            ],
        ];

        foreach ($materials as $item) {
            $stock = $item['stock'];
            $material = Material::updateOrCreate(
                ['code' => $item['code']],
                $item
            );

            // Log mutation awal jika belum ada
            if ($material->mutations()->count() === 0) {
                $material->mutations()->create([
                    'user_id' => $adminId,
                    'type' => 'in',
                    'amount' => $stock,
                    'stock_before' => 0,
                    'stock_after' => $stock,
                    'notes' => 'Stok awal inisialisasi master data material Banterpool',
                ]);
            }
        }
    }
}
