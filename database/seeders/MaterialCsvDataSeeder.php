<?php

namespace Database\Seeders;

use App\Models\Material;
use App\Models\MaterialMonthlyStock;
use App\Models\MaterialMutation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MaterialCsvDataSeeder extends Seeder
{
    /**
     * Parse and import CSV data into materials and material_mutations tables.
     */
    public function run(): void
    {
        $csvPath = database_path('data/stok_barang.csv');
        if (!file_exists($csvPath)) {
            $this->command?->error("File {$csvPath} tidak ditemukan!");
            return;
        }

        $lines = file($csvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (empty($lines)) {
            $this->command?->error("File CSV kosong!");
            return;
        }

        $adminUser = User::first();
        $adminId = $adminUser ? $adminUser->id : null;

        // Kosongkan data lama agar sesuai dengan file CSV yang dikirimkan
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            MaterialMutation::truncate();
            MaterialMonthlyStock::truncate();
            Material::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } else {
            MaterialMutation::query()->delete();
            MaterialMonthlyStock::query()->delete();
            Material::query()->delete();
        }

        $materialRows = [];
        $incomingRows = [];
        $outgoingRows = [];

        // Data dimulai dari baris index 6 (setelah header baris 5)
        for ($i = 6; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if (empty($line)) continue;

            $cols = explode(';', $line);
            // Bersihkan setiap kolom
            $cols = array_map('trim', $cols);

            // 1. Kolom Material Master (0 - 5)
            $code = $cols[0] ?? '';
            $name = $cols[1] ?? '';
            $stokAwalStr = $cols[2] ?? '';
            $masukStr = $cols[3] ?? '';
            $keluarStr = $cols[4] ?? '';
            $stokAkhirStr = $cols[5] ?? '';

            if (!empty($code) && !empty($name)) {
                $stokAwal = $this->parseNumber($stokAwalStr);
                $totalIn = $this->parseNumber($masukStr);
                $totalOut = $this->parseNumber($keluarStr);
                $stokAkhir = $stokAkhirStr !== '' ? $this->parseNumber($stokAkhirStr) : ($stokAwal + $totalIn - $totalOut);

                $category = $this->detectCategory($name);
                $unit = $this->detectUnit($name, $category);

                $materialRows[$code] = [
                    'code' => $code,
                    'name' => $name,
                    'category' => $category,
                    'unit' => $unit,
                    'month' => 'Oktober',
                    'year' => 2026,
                    'initial_stock' => $stokAwal,
                    'total_in' => $totalIn,
                    'total_out' => $totalOut,
                    'stock' => $stokAkhir,
                    'min_stock' => 5,
                    'unit_price' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // 2. Kolom Barang Masuk (7 - 10)
            $tglMasuk = $cols[7] ?? '';
            $namaMasuk = $cols[8] ?? '';
            $jmlMasukStr = $cols[9] ?? '';
            $catatanMasuk = $cols[10] ?? '';

            if (!empty($tglMasuk) && !empty($namaMasuk) && $jmlMasukStr !== '') {
                $incomingRows[] = [
                    'date' => $this->parseDate($tglMasuk),
                    'name' => $namaMasuk,
                    'amount' => $this->parseNumber($jmlMasukStr),
                    'notes' => $catatanMasuk ?: 'Barang Masuk',
                ];
            }

            // 3. Kolom Barang Keluar (12 - 15)
            $tglKeluar = $cols[12] ?? '';
            $namaKeluar = $cols[13] ?? '';
            $jmlKeluarStr = $cols[14] ?? '';
            $catatanKeluar = $cols[15] ?? '';

            if (!empty($tglKeluar) && !empty($namaKeluar) && $jmlKeluarStr !== '') {
                $outgoingRows[] = [
                    'date' => $this->parseDate($tglKeluar),
                    'name' => $namaKeluar,
                    'amount' => $this->parseNumber($jmlKeluarStr),
                    'notes' => $catatanKeluar ?: 'Barang Keluar',
                ];
            }
        }

        // Insert Master Materials
        $materialMap = []; // code/name -> Material model
        foreach ($materialRows as $code => $data) {
            $mat = Material::create($data);
            $materialMap[$code] = $mat;
            // Also map by clean lowercase name
            $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $mat->name));
            $materialMap['name_' . $cleanName] = $mat;

            // Simpan snapshot bulanan untuk periode Oktober 2026
            MaterialMonthlyStock::create([
                'material_id' => $mat->id,
                'month' => 10,
                'month_name' => 'Oktober',
                'year' => 2026,
                'initial_stock' => $mat->initial_stock,
                'total_in' => $mat->total_in,
                'total_out' => $mat->total_out,
                'final_stock' => $mat->stock,
            ]);
        }

        $this->command?->info("Berhasil membuat " . count($materialRows) . " master barang.");

        // Helper untuk mencari material berdasarkan nama
        $findMaterial = function ($name) use (&$materialMap) {
            $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
            if (isset($materialMap['name_' . $cleanName])) {
                return $materialMap['name_' . $cleanName];
            }

            // Fuzzy lookup
            foreach ($materialMap as $key => $mat) {
                if (str_starts_with($key, 'name_')) {
                    $k = substr($key, 5);
                    if ($k === $cleanName || str_contains($k, $cleanName) || str_contains($cleanName, $k)) {
                        return $mat;
                    }
                }
            }
            return null;
        };

        // Insert Mutasi Barang Masuk
        $inCount = 0;
        foreach ($incomingRows as $in) {
            $mat = $findMaterial($in['name']);
            if ($mat) {
                MaterialMutation::create([
                    'material_id' => $mat->id,
                    'user_id' => $adminId,
                    'type' => 'in',
                    'transaction_date' => $in['date'],
                    'amount' => $in['amount'],
                    'stock_before' => $mat->initial_stock,
                    'stock_after' => $mat->initial_stock + $in['amount'],
                    'notes' => $in['notes'] . ' (Barang Masuk)',
                    'created_at' => Carbon::parse($in['date'])->setTime(9, 0, 0),
                    'updated_at' => Carbon::parse($in['date'])->setTime(9, 0, 0),
                ]);
                $inCount++;
            }
        }
        $this->command?->info("Berhasil membuat {$inCount} catatan mutasi Barang Masuk.");

        // Insert Mutasi Barang Keluar
        $outCount = 0;
        foreach ($outgoingRows as $out) {
            $mat = $findMaterial($out['name']);
            if ($mat) {
                MaterialMutation::create([
                    'material_id' => $mat->id,
                    'user_id' => $adminId,
                    'type' => 'out',
                    'transaction_date' => $out['date'],
                    'amount' => $out['amount'],
                    'stock_before' => $mat->stock + $out['amount'],
                    'stock_after' => $mat->stock,
                    'notes' => $out['notes'] . ' (Barang Keluar)',
                    'created_at' => Carbon::parse($out['date'])->setTime(14, 0, 0),
                    'updated_at' => Carbon::parse($out['date'])->setTime(14, 0, 0),
                ]);
                $outCount++;
            }
        }
        $this->command?->info("Berhasil membuat {$outCount} catatan mutasi Barang Keluar.");
    }

    private function parseNumber(string $val): float
    {
        $val = trim($val);
        if ($val === '') return 0.0;
        // Ganti koma dengan titik (format desimal Indonesia 0,5 -> 0.5)
        $val = str_replace(',', '.', $val);
        // Hapus karakter non angka, minus, atau titik
        $val = preg_replace('/[^0-9\.\-]/', '', $val);
        return (float) $val;
    }

    private function parseDate(string $val): string
    {
        $val = trim($val);
        try {
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $val, $m)) {
                // MM/DD/YYYY (contoh: 10/01/2026 -> 2026-10-01)
                return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[1], (int)$m[2]);
            }
            return Carbon::parse($val)->toDateString();
        } catch (\Exception $e) {
            return now()->toDateString();
        }
    }

    private function detectCategory(string $name): string
    {
        $lower = strtolower($name);
        if (str_contains($lower, 'tiang')) return 'Tiang & Bracket';
        if (str_contains($lower, 'slack')) return 'Aksesoris & Bracket';
        if (str_contains($lower, 'clamp') || str_contains($lower, 'klamp') || str_contains($lower, 'helical') || str_contains($lower, 'helocal')) return 'Clamp & Aksesoris';
        if (str_contains($lower, 'joint closure') || str_contains($lower, 'closure')) return 'Joint Closure';
        if (str_contains($lower, 'odp')) return 'ODP & Box Pasif';
        if (str_contains($lower, 'dc ') || str_contains($lower, 'jellytube') || str_contains($lower, 'fig8') || str_contains($lower, 'armore') || str_contains($lower, 'mini adss') || str_contains($lower, 'ku ')) return 'Kabel Drop & Fiber Optik';
        if (str_contains($lower, 'kabel lan')) return 'Kabel LAN & UTP';
        if (str_contains($lower, 'pigtail') || str_contains($lower, 'patchcore') || str_contains($lower, 'patchcord')) return 'Patchcord & Pigtail';
        if (str_contains($lower, 'adapter')) return 'Adapter & Konektor Optik';
        if (str_contains($lower, 'spliter') || str_contains($lower, 'splitter')) return 'Splitter Optik';
        if (str_contains($lower, 'router') || str_contains($lower, 'ap outdoor')) return 'Router & AP Wireless';
        if (str_contains($lower, 'modem')) return 'Modem & ONT GPON';
        if (str_contains($lower, 'mikrotik') || str_contains($lower, 'ubnt') || str_contains($lower, 'switch') || str_contains($lower, 'ups')) return 'Perangkat Jaringan & Switch';
        if (str_contains($lower, 'htb')) return 'Media Converter (HTB)';
        if (str_contains($lower, 'adaptor') || str_contains($lower, 'poe')) return 'Power & Adaptor';
        if (str_contains($lower, 'smuft')) return 'Protection Sleeve (Smuft)';
        if (str_contains($lower, 'tist')) return 'Kabel Ties';
        if (str_contains($lower, 'solasi') || str_contains($lower, 'double foam') || str_contains($lower, 'raber') || str_contains($lower, 'preco') || str_contains($lower, 'pelindung') || str_contains($lower, 'roset') || str_contains($lower, 'rg45') || str_contains($lower, 'tray kaset') || str_contains($lower, 'tutup tiang')) return 'Material Pembantu & Instalasi';
        return 'Material Optik & Jaringan';
    }

    private function detectUnit(string $name, string $category): string
    {
        $lower = strtolower($name);
        if (str_contains($lower, 'tiang')) return 'Batang';
        if (str_contains($lower, 'dc ') || str_contains($lower, 'jellytube') || str_contains($lower, 'fig8') || str_contains($lower, 'armore') || str_contains($lower, 'mini adss') || str_contains($lower, 'ku ')) return 'Meter';
        if (str_contains($lower, 'kabel lan')) return 'Meter';
        if (str_contains($lower, 'patchcore') || str_contains($lower, 'pigtail')) return 'Pcs';
        if (str_contains($lower, 'solasi') || str_contains($lower, 'raber') || str_contains($lower, 'double foam')) return 'Roll';
        if (str_contains($lower, 'router') || str_contains($lower, 'modem') || str_contains($lower, 'switch') || str_contains($lower, 'htb') || str_contains($lower, 'adaptor') || str_contains($lower, 'ap ') || str_contains($lower, 'ups') || str_contains($lower, 'ubnt') || str_contains($lower, 'mikrotik')) return 'Unit';
        return 'Pcs';
    }
}
