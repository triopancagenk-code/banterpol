<?php

namespace App\Services;

class PriceListService
{
    /**
     * Price List Jasa 2026 (Diambil dari Price List Jasa 2026.xlsx)
     * Total: 73 item jasa.
     */
    public const ITEMS = [
        [
            'no' => 1,
            'type' => 'Pemasangan Baru',
            'description' => 'Jasa Pasang Baru Pelanggan',
            'code' => 'NPSB',
            'price' => 150000,
            'unit' => 'Client',
        ],
        [
            'no' => 2,
            'type' => 'Pengembangan',
            'description' => 'Jasa Pasang Baru OTB 12C',
            'code' => 'NOTB12',
            'price' => 450000,
            'unit' => 'Unit',
        ],
        [
            'no' => 3,
            'type' => 'Pengembangan',
            'description' => 'Jasa Pasang Baru OTB 24C',
            'code' => 'NOTB24',
            'price' => 650000,
            'unit' => 'Unit',
        ],
        [
            'no' => 4,
            'type' => 'Pengembangan',
            'description' => 'Jasa Pasang Baru OTB 48C',
            'code' => 'NOTB48',
            'price' => 850000,
            'unit' => 'Unit',
        ],
        [
            'no' => 5,
            'type' => 'Pengembangan',
            'description' => 'Jasa Pasang Baru OTB 96C',
            'code' => 'NOTB96',
            'price' => 1200000,
            'unit' => 'Unit',
        ],
        [
            'no' => 6,
            'type' => 'Pengembangan',
            'description' => 'Jasa Pemasangan ODC 8C',
            'code' => 'NODC8',
            'price' => 250000,
            'unit' => 'Unit',
        ],
        [
            'no' => 7,
            'type' => 'Pengembangan',
            'description' => 'Jasa Pemasangan ODC 16C',
            'code' => 'NODC16',
            'price' => 350000,
            'unit' => 'Unit',
        ],
        [
            'no' => 8,
            'type' => 'Pengembangan',
            'description' => 'Jasa Pemasangan ODP 8C',
            'code' => 'NODP8',
            'price' => 230000,
            'unit' => 'Unit',
        ],
        [
            'no' => 9,
            'type' => 'Pengembangan',
            'description' => 'Jasa Pemasangan ODP 16C',
            'code' => 'NODP16',
            'price' => 250000,
            'unit' => 'Unit',
        ],
        [
            'no' => 10,
            'type' => 'Pengembangan',
            'description' => 'Jasa Pemasngan Clousure 4C',
            'code' => 'NCLSR4',
            'price' => 50000,
            'unit' => 'Unit',
        ],
        [
            'no' => 11,
            'type' => 'Pengembangan',
            'description' => 'Jasa Pemasangn Clousure 6C',
            'code' => 'NCLSR6',
            'price' => 60000,
            'unit' => 'Unit',
        ],
        [
            'no' => 12,
            'type' => 'Pengembangan',
            'description' => 'Jasa Pemasangan Clousure 8C',
            'code' => 'NCLSR8',
            'price' => 70000,
            'unit' => 'Unit',
        ],
        [
            'no' => 13,
            'type' => 'Pengembangan',
            'description' => 'Jasa Pemasangan Clousure 12C',
            'code' => 'NCLSR12',
            'price' => 110000,
            'unit' => 'Unit',
        ],
        [
            'no' => 14,
            'type' => 'Pengembangan',
            'description' => 'Jasa Pemasangan Clousure 24C',
            'code' => 'NCLSR24',
            'price' => 150000,
            'unit' => 'Unit',
        ],
        [
            'no' => 15,
            'type' => 'Pengembangan',
            'description' => 'Jasa Pemasangan Clousure 48C',
            'code' => 'NCLSR48',
            'price' => 20000,
            'unit' => 'Unit',
        ],
        [
            'no' => 16,
            'type' => 'Pengembangan',
            'description' => 'Jasa Pemasangan Clousure 96C',
            'code' => 'NCLSR96',
            'price' => 250000,
            'unit' => 'Unit',
        ],
        [
            'no' => 17,
            'type' => 'Pengembangan',
            'description' => 'Jasa Penarikan Kabel DC1C',
            'code' => 'NKBDC1',
            'price' => 500,
            'unit' => 'M',
        ],
        [
            'no' => 18,
            'type' => 'Pengembangan',
            'description' => 'Jasa Penarikan Kabel DC2C',
            'code' => 'NKBDC2',
            'price' => 700,
            'unit' => 'M',
        ],
        [
            'no' => 19,
            'type' => 'Pengembangan',
            'description' => 'Jasa Penarikan Kabel DC4C',
            'code' => 'NKBDC4',
            'price' => 800,
            'unit' => 'M',
        ],
        [
            'no' => 20,
            'type' => 'Pengembangan',
            'description' => 'Jasa Penarikan Kabel Jellytube 2C',
            'code' => 'NKBJT2',
            'price' => 1000,
            'unit' => 'M',
        ],
        [
            'no' => 21,
            'type' => 'Pengembangan',
            'description' => 'Jasa Penarikan Kabel Jellytube 4C',
            'code' => 'NKBJT4',
            'price' => 1000,
            'unit' => 'M',
        ],
        [
            'no' => 22,
            'type' => 'Pengembangan',
            'description' => 'Jasa Penarikan Kabel Jellytube 6C',
            'code' => 'NKBJT6',
            'price' => 1100,
            'unit' => 'M',
        ],
        [
            'no' => 23,
            'type' => 'Pengembangan',
            'description' => 'Jasa Penarikan Kabel Jellytube 8C',
            'code' => 'NKBJT8',
            'price' => 1200,
            'unit' => 'M',
        ],
        [
            'no' => 24,
            'type' => 'Pengembangan',
            'description' => 'Jasa Penarikan Kabel Jellytube 12C',
            'code' => 'NKBJT12',
            'price' => 1500,
            'unit' => 'M',
        ],
        [
            'no' => 25,
            'type' => 'Pengembangan',
            'description' => 'Jasa Penarikan Kabel Fig8 2C',
            'code' => 'NKBF82',
            'price' => 1000,
            'unit' => 'M',
        ],
        [
            'no' => 26,
            'type' => 'Pengembangan',
            'description' => 'Jasa Penarikan Kabel Fig8 4C',
            'code' => 'NKBF84',
            'price' => 1000,
            'unit' => 'M',
        ],
        [
            'no' => 27,
            'type' => 'Pengembangan',
            'description' => 'Jasa Penarikan Kabel Fig8 6C',
            'code' => 'NKBF86',
            'price' => 1200,
            'unit' => 'M',
        ],
        [
            'no' => 28,
            'type' => 'Pengembangan',
            'description' => 'Jasa Penarikan Kabel Fig8 8C',
            'code' => 'NKBF88',
            'price' => 1200,
            'unit' => 'M',
        ],
        [
            'no' => 29,
            'type' => 'Pengembangan',
            'description' => 'Jasa Penarikan Kabel Fig8 12C',
            'code' => 'NKBF824',
            'price' => 1500,
            'unit' => 'M',
        ],
        [
            'no' => 30,
            'type' => 'Pengembangan',
            'description' => 'Jasa Penarikan Kabel Fig8 24C',
            'code' => 'NKBF813',
            'price' => 1600,
            'unit' => 'M',
        ],
        [
            'no' => 31,
            'type' => 'Pengembangan',
            'description' => 'Jasa Penarikan Kabel Mini ADSS 8C',
            'code' => 'NKBMADSS8',
            'price' => 1500,
            'unit' => 'M',
        ],
        [
            'no' => 32,
            'type' => 'Pengembangan',
            'description' => 'Jasa Penarikan Kabel Mini ADSS 12C',
            'code' => 'NKBMADS12',
            'price' => 1500,
            'unit' => 'M',
        ],
        [
            'no' => 33,
            'type' => 'Pengembangan',
            'description' => 'Jasa Penarikan Kabel Mini ADSS 24C',
            'code' => 'NKBMADSS24',
            'price' => 1700,
            'unit' => 'M',
        ],
        [
            'no' => 34,
            'type' => 'Pengembangan',
            'description' => 'Jasa Penarikan Kabel Mini ADSS 48C',
            'code' => 'NKBMADSS48',
            'price' => 2000,
            'unit' => 'M',
        ],
        [
            'no' => 35,
            'type' => 'Pengembangan',
            'description' => 'Jasa Penarikan Kabel ADSS/KU 8C',
            'code' => 'NKBKU8',
            'price' => 1500,
            'unit' => 'M',
        ],
        [
            'no' => 36,
            'type' => 'Pengembangan',
            'description' => 'Jasa Penarikan Kabel ADSS/KU 12',
            'code' => 'NKBKU12',
            'price' => 1500,
            'unit' => 'M',
        ],
        [
            'no' => 37,
            'type' => 'Pengembangan',
            'description' => 'Jasa Penarikan Kabel ADSS/KU 24C',
            'code' => 'NKBKU24',
            'price' => 2000,
            'unit' => 'M',
        ],
        [
            'no' => 38,
            'type' => 'Pengembangan',
            'description' => 'Jasa Penarikan Kabel ADSS/KU 48C',
            'code' => 'NKBKU48',
            'price' => 2000,
            'unit' => 'M',
        ],
        [
            'no' => 39,
            'type' => 'Pengembangan',
            'description' => 'Jasa Penarikan Kabel ADSS/KU 96C',
            'code' => 'NKBKU96',
            'price' => 2500,
            'unit' => 'M',
        ],
        [
            'no' => 40,
            'type' => 'Pengembangan',
            'description' => 'Jasa Pemasangan Tiang T6',
            'code' => 'NTGT6',
            'price' => 150000,
            'unit' => 'Batang',
        ],
        [
            'no' => 41,
            'type' => 'Pengembangan',
            'description' => 'Jasa Pemasangan Tiang T7',
            'code' => 'NTGT7',
            'price' => 150000,
            'unit' => 'Batang',
        ],
        [
            'no' => 42,
            'type' => 'Pengembangan',
            'description' => 'Jasa Pemasangan Tiang T9',
            'code' => 'NTGT9',
            'price' => 160000,
            'unit' => 'Batang',
        ],
        [
            'no' => 43,
            'type' => 'Pengembangan',
            'description' => 'Jasa Pemasangan Acesoris Tiang',
            'code' => 'NTGACC',
            'price' => 10000,
            'unit' => 'Pcs',
        ],
        [
            'no' => 44,
            'type' => 'Pengembangan',
            'description' => 'Jasa Pemasnagan Slack suport',
            'code' => 'NTGSLKS',
            'price' => 20000,
            'unit' => 'Pcs',
        ],
        [
            'no' => 45,
            'type' => 'Maintenance',
            'description' => 'Jasa Perbaikan Ganti Router Pelanggan',
            'code' => 'JSGRTR',
            'price' => 50000,
            'unit' => 'Unit',
        ],
        [
            'no' => 46,
            'type' => 'Maintenance',
            'description' => 'Jasa Perbaikan Pelanggan Los',
            'code' => 'JSCLNLO',
            'price' => 100000,
            'unit' => 'Client',
        ],
        [
            'no' => 47,
            'type' => 'Maintenance',
            'description' => 'Jasa Perbaikan Ganti Adaptor Pelanggan',
            'code' => 'JSGADCL',
            'price' => 30000,
            'unit' => 'Unit',
        ],
        [
            'no' => 48,
            'type' => 'Maintenance',
            'description' => 'Jasa Perbaikan Pelanggan Ganti HTB 1C',
            'code' => 'JSGTHTB1',
            'price' => 30000,
            'unit' => 'Unit',
        ],
        [
            'no' => 49,
            'type' => 'Maintenance',
            'description' => 'Jasa Perbaikan Pelanggan Ganti HTB 2C',
            'code' => 'JSGTHTB2',
            'price' => 40000,
            'unit' => 'Unit',
        ],
        [
            'no' => 50,
            'type' => 'Maintenance',
            'description' => 'Jasa Perbaikan Pelanggan Ganti HTB 3C',
            'code' => 'JSGTHTB3',
            'price' => 50000,
            'unit' => 'Unit',
        ],
        [
            'no' => 51,
            'type' => 'Maintenance',
            'description' => 'Jasa Perbaikan Pelanggan Ganti HTB 4C',
            'code' => 'JSGTHTB4',
            'price' => 60000,
            'unit' => 'Unit',
        ],
        [
            'no' => 52,
            'type' => 'Maintenance',
            'description' => 'Jasa Perbaikan Pelanggan Ganti HTB 6C',
            'code' => 'JSGTHTB6',
            'price' => 80000,
            'unit' => 'Unit',
        ],
        [
            'no' => 53,
            'type' => 'Maintenance',
            'description' => 'Jasa Perbaikan Pelanggan Gabti Hubswitch',
            'code' => 'JSGTHS',
            'price' => 40000,
            'unit' => 'Unit',
        ],
        [
            'no' => 54,
            'type' => 'Maintenance',
            'description' => 'Jasa Perbaikan ODC 8C',
            'code' => 'JSPODC8',
            'price' => 170000,
            'unit' => 'Unit',
        ],
        [
            'no' => 55,
            'type' => 'Maintenance',
            'description' => 'Jasa Perbaikan ODC 16C',
            'code' => 'JSPODC16',
            'price' => 200000,
            'unit' => 'Unit',
        ],
        [
            'no' => 56,
            'type' => 'Maintenance',
            'description' => 'Jasa Perbaikan ODP 8C',
            'code' => 'JSPODP8',
            'price' => 150000,
            'unit' => 'Unit',
        ],
        [
            'no' => 57,
            'type' => 'Maintenance',
            'description' => 'Jasa Perbaikan ODP16C',
            'code' => 'JSPODP16',
            'price' => 170000,
            'unit' => 'Unit',
        ],
        [
            'no' => 58,
            'type' => 'Maintenance',
            'description' => 'Jasa Splice',
            'code' => 'JSSKBL',
            'price' => 30000,
            'unit' => 'Titik',
        ],
        [
            'no' => 59,
            'type' => 'Maintenance',
            'description' => 'Jasa Replace Spliter 2C',
            'code' => 'JRSP2C',
            'price' => 30000,
            'unit' => 'Pcs',
        ],
        [
            'no' => 60,
            'type' => 'Maintenance',
            'description' => 'Jasa Replace Spliter 4C',
            'code' => 'JRSP4C',
            'price' => 40000,
            'unit' => 'Pcs',
        ],
        [
            'no' => 61,
            'type' => 'Maintenance',
            'description' => 'Jasa Replace Spliter 8C',
            'code' => 'JRSP8C',
            'price' => 60000,
            'unit' => 'Pcs',
        ],
        [
            'no' => 62,
            'type' => 'Maintenance',
            'description' => 'Jasa Replace Spliter 16C',
            'code' => 'JRSP16C',
            'price' => 100000,
            'unit' => 'Pcs',
        ],
        [
            'no' => 63,
            'type' => 'Maintenance',
            'description' => 'Jasa Migrasi Tanpa Instal Kabel',
            'code' => 'JMGTKB',
            'price' => 100000,
            'unit' => 'Client',
        ],
        [
            'no' => 64,
            'type' => 'Maintenance',
            'description' => 'Jasa Migrasi Include instal Kabel',
            'code' => 'JMGIKB',
            'price' => 150000,
            'unit' => 'Client',
        ],
        [
            'no' => 65,
            'type' => 'Maintenance',
            'description' => 'Jasa Perbaikan Jalur 1C',
            'code' => 'JSTRCK1',
            'price' => 60000,
            'unit' => 'Event',
        ],
        [
            'no' => 66,
            'type' => 'Maintenance',
            'description' => 'Jasa Perbaikan Jalur 2C',
            'code' => 'JSTRCK2',
            'price' => 70000,
            'unit' => 'Event',
        ],
        [
            'no' => 67,
            'type' => 'Maintenance',
            'description' => 'Jasa Perbaikan Jalur 4C',
            'code' => 'JSTRCK4',
            'price' => 10000,
            'unit' => 'Event',
        ],
        [
            'no' => 68,
            'type' => 'Maintenance',
            'description' => 'Jasa Perbaikan Jalur 6C',
            'code' => 'JSTRCK6',
            'price' => 120000,
            'unit' => 'Event',
        ],
        [
            'no' => 69,
            'type' => 'Maintenance',
            'description' => 'Jasa Perbaikan Jalur 8C',
            'code' => 'JSTRCK8',
            'price' => 200000,
            'unit' => 'Event',
        ],
        [
            'no' => 70,
            'type' => 'Maintenance',
            'description' => 'Jasa Perbaikan Jalur 12C',
            'code' => 'JSTRCK12',
            'price' => 250000,
            'unit' => 'Event',
        ],
        [
            'no' => 71,
            'type' => 'Maintenance',
            'description' => 'Jasa Perbaikan Jalur 24C',
            'code' => 'JSTRCK24',
            'price' => 500000,
            'unit' => 'Event',
        ],
        [
            'no' => 72,
            'type' => 'Maintenance',
            'description' => 'Jasa Perbaikan Jalur 48C',
            'code' => 'JSTRCK48',
            'price' => 1000000,
            'unit' => 'Event',
        ],
        [
            'no' => 73,
            'type' => 'Maintenance',
            'description' => 'Jasa Perbaikan Jalur 96C',
            'code' => 'JSTRCK96',
            'price' => 1500000,
            'unit' => 'Event',
        ],
    ];

    /**
     * Dapatkan semua item daftar harga jasa.
     */
    public static function all(): array
    {
        return self::ITEMS;
    }

    /**
     * Dapatkan daftar kategori/tipe pekerjaan unik.
     */
    public static function getTypes(): array
    {
        $types = [];
        foreach (self::ITEMS as $item) {
            if (!in_array($item['type'], $types, true)) {
                $types[] = $item['type'];
            }
        }
        return $types;
    }

    /**
     * Dapatkan item jasa yang dikelompokkan berdasarkan tipe pekerjaan.
     */
    public static function groupedByType(): array
    {
        $grouped = [];
        foreach (self::ITEMS as $item) {
            $grouped[$item['type']][] = $item;
        }
        return $grouped;
    }

    /**
     * Cari item jasa berdasarkan kode pekerjaan (e.g. 'NPSB').
     */
    public static function findByCode(string $code): ?array
    {
        $target = strtoupper(trim($code));
        foreach (self::ITEMS as $item) {
            if (strtoupper($item['code']) === $target) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Cari item jasa berdasarkan deskripsi/nama pekerjaan (fuzzy match).
     */
    public static function findByDescription(string $search): ?array
    {
        $target = strtolower(trim($search));
        if (empty($target)) {
            return null;
        }

        // Exact match
        foreach (self::ITEMS as $item) {
            if (strtolower($item['description']) === $target) {
                return $item;
            }
        }

        // Substring match
        foreach (self::ITEMS as $item) {
            if (str_contains(strtolower($item['description']), $target) || str_contains($target, strtolower($item['description']))) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Format Rupiah helper
     */
    public static function formatRupiah(float $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}