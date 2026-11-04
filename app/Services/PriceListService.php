<?php

namespace App\Services;

class PriceListService
{
    /**
     * Price List Jasa 2026 (Sheet 1: Services)
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
     * Price List Material 2026 (Sheet 2: Materials)
     * Total: 102 item material resmi.
     */
    public const MATERIALS = [
        [
            'no' => 1,
            'description' => 'Kabel DC 1C',
            'code' => '',
            'price' => 750,
            'unit' => 'M',
        ],
        [
            'no' => 2,
            'description' => 'Kabel DC 2C',
            'code' => '',
            'price' => 900,
            'unit' => 'M',
        ],
        [
            'no' => 3,
            'description' => 'Kabel DC 4C',
            'code' => '',
            'price' => 1400,
            'unit' => 'M',
        ],
        [
            'no' => 4,
            'description' => 'Kabel Jelytube 2C',
            'code' => '',
            'price' => 1300,
            'unit' => 'M',
        ],
        [
            'no' => 5,
            'description' => 'Kabel Jelytube 4C',
            'code' => '',
            'price' => 1800,
            'unit' => 'M',
        ],
        [
            'no' => 6,
            'description' => 'Kabel Jelytube 6C',
            'code' => '',
            'price' => 0,
            'unit' => 'M',
        ],
        [
            'no' => 7,
            'description' => 'Kabel Jelytube 8C',
            'code' => '',
            'price' => 0,
            'unit' => 'M',
        ],
        [
            'no' => 8,
            'description' => 'Kabel Jelytube 12C',
            'code' => '',
            'price' => 0,
            'unit' => 'M',
        ],
        [
            'no' => 9,
            'description' => 'Kabel Fig8 2C',
            'code' => '',
            'price' => 2100,
            'unit' => 'M',
        ],
        [
            'no' => 10,
            'description' => 'Kabel Fig8 4C',
            'code' => '',
            'price' => 2300,
            'unit' => 'M',
        ],
        [
            'no' => 11,
            'description' => 'Kabel Fig8 6C',
            'code' => '',
            'price' => 2400,
            'unit' => 'M',
        ],
        [
            'no' => 12,
            'description' => 'Kabel Fig8 8C',
            'code' => '',
            'price' => 2700,
            'unit' => 'M',
        ],
        [
            'no' => 13,
            'description' => 'Kabel Fig8 12C',
            'code' => '',
            'price' => 3100,
            'unit' => 'M',
        ],
        [
            'no' => 14,
            'description' => 'Kabel Mini ADSS 8C',
            'code' => '',
            'price' => 3500,
            'unit' => 'M',
        ],
        [
            'no' => 15,
            'description' => 'Kabel Mini ADSS 12C',
            'code' => '',
            'price' => 3800,
            'unit' => 'M',
        ],
        [
            'no' => 16,
            'description' => 'Kabel Mini ADSS 24C',
            'code' => '',
            'price' => 6500,
            'unit' => 'M',
        ],
        [
            'no' => 17,
            'description' => 'Kabel Armore 8C',
            'code' => '',
            'price' => 3700,
            'unit' => 'M',
        ],
        [
            'no' => 18,
            'description' => 'Kabel Armore 12C',
            'code' => '',
            'price' => 6500,
            'unit' => 'M',
        ],
        [
            'no' => 19,
            'description' => 'Kadel ADSS/KU 12C',
            'code' => '',
            'price' => 4100,
            'unit' => 'M',
        ],
        [
            'no' => 20,
            'description' => 'Kadel ADSS/KU 24C',
            'code' => '',
            'price' => 0,
            'unit' => 'M',
        ],
        [
            'no' => 21,
            'description' => 'Kadel ADSS/KU 48C',
            'code' => '',
            'price' => 12500,
            'unit' => 'M',
        ],
        [
            'no' => 22,
            'description' => 'Kadel ADSS/KU 96C',
            'code' => '',
            'price' => 0,
            'unit' => 'M',
        ],
        [
            'no' => 23,
            'description' => 'Kabel Lan Cat5',
            'code' => '',
            'price' => 3000,
            'unit' => 'M',
        ],
        [
            'no' => 24,
            'description' => 'Kabel Lan Cat6',
            'code' => '',
            'price' => 5000,
            'unit' => 'M',
        ],
        [
            'no' => 25,
            'description' => 'HTB 1C',
            'code' => '',
            'price' => 70000,
            'unit' => 'Unit',
        ],
        [
            'no' => 26,
            'description' => 'HTB 2C',
            'code' => '',
            'price' => 150000,
            'unit' => 'Unit',
        ],
        [
            'no' => 27,
            'description' => 'HTB 3C',
            'code' => '',
            'price' => 190000,
            'unit' => 'Unit',
        ],
        [
            'no' => 28,
            'description' => 'HTB 4C',
            'code' => '',
            'price' => 310000,
            'unit' => 'Unit',
        ],
        [
            'no' => 29,
            'description' => 'HTB 6C',
            'code' => '',
            'price' => 350000,
            'unit' => 'Unit',
        ],
        [
            'no' => 30,
            'description' => 'Spliter 1:2',
            'code' => '',
            'price' => 55000,
            'unit' => 'Unit',
        ],
        [
            'no' => 31,
            'description' => 'Spliter 1:4',
            'code' => '',
            'price' => 60000,
            'unit' => 'Unit',
        ],
        [
            'no' => 32,
            'description' => 'Spliter 1:8',
            'code' => '',
            'price' => 75000,
            'unit' => 'Unit',
        ],
        [
            'no' => 33,
            'description' => 'Spliter 1:16',
            'code' => '',
            'price' => 120000,
            'unit' => 'Unit',
        ],
        [
            'no' => 34,
            'description' => 'BOX ODP 8C',
            'code' => '',
            'price' => 110000,
            'unit' => 'Unit',
        ],
        [
            'no' => 35,
            'description' => 'BOX ODP 16C',
            'code' => '',
            'price' => 150000,
            'unit' => 'Unit',
        ],
        [
            'no' => 36,
            'description' => 'BOX ODP 24C',
            'code' => '',
            'price' => 300000,
            'unit' => 'Unit',
        ],
        [
            'no' => 37,
            'description' => 'Clousure 4C',
            'code' => '',
            'price' => 55000,
            'unit' => 'Unit',
        ],
        [
            'no' => 38,
            'description' => 'Clousure 8C',
            'code' => '',
            'price' => 65000,
            'unit' => 'Unit',
        ],
        [
            'no' => 39,
            'description' => 'Clousure 12C',
            'code' => '',
            'price' => 70000,
            'unit' => 'Unit',
        ],
        [
            'no' => 40,
            'description' => 'Clousure 48C',
            'code' => '',
            'price' => 450000,
            'unit' => 'Unit',
        ],
        [
            'no' => 41,
            'description' => 'Clousure 96C',
            'code' => '',
            'price' => 1200000,
            'unit' => 'Unit',
        ],
        [
            'no' => 42,
            'description' => 'OTB 12C',
            'code' => '',
            'price' => 650000,
            'unit' => 'Unit',
        ],
        [
            'no' => 43,
            'description' => 'OTB 24C',
            'code' => '',
            'price' => 950000,
            'unit' => 'Unit',
        ],
        [
            'no' => 44,
            'description' => 'OTB 48C',
            'code' => '',
            'price' => 1550000,
            'unit' => 'Unit',
        ],
        [
            'no' => 45,
            'description' => 'OTB 96C',
            'code' => '',
            'price' => 2500000,
            'unit' => 'Unit',
        ],
        [
            'no' => 46,
            'description' => 'Pigtail',
            'code' => '',
            'price' => 6000,
            'unit' => 'Pcs',
        ],
        [
            'no' => 47,
            'description' => 'Patchcore SC/UPC',
            'code' => '',
            'price' => 7000,
            'unit' => 'Pcs',
        ],
        [
            'no' => 48,
            'description' => 'Patchcore LC/UPC',
            'code' => '',
            'price' => 50000,
            'unit' => 'Pcs',
        ],
        [
            'no' => 49,
            'description' => 'Patchcore SC/UPC Hijau',
            'code' => '',
            'price' => 8000,
            'unit' => 'Pcs',
        ],
        [
            'no' => 50,
            'description' => 'Patchcore LC-UPC/SC-UPC',
            'code' => '',
            'price' => 21000,
            'unit' => 'Pcs',
        ],
        [
            'no' => 51,
            'description' => 'Roset',
            'code' => '',
            'price' => 6000,
            'unit' => 'Unit',
        ],
        [
            'no' => 52,
            'description' => 'PreCo',
            'code' => '',
            'price' => 25000,
            'unit' => 'Unit',
        ],
        [
            'no' => 53,
            'description' => 'Router GM220',
            'code' => '',
            'price' => 140000,
            'unit' => 'Unit',
        ],
        [
            'no' => 54,
            'description' => 'Router Huawei',
            'code' => '',
            'price' => 160000,
            'unit' => 'Unit',
        ],
        [
            'no' => 55,
            'description' => 'Router Fiber Home',
            'code' => '',
            'price' => 160000,
            'unit' => 'Unit',
        ],
        [
            'no' => 56,
            'description' => 'Router ZTE 660',
            'code' => '',
            'price' => 160000,
            'unit' => 'Unit',
        ],
        [
            'no' => 57,
            'description' => 'Router ZTE 609',
            'code' => '',
            'price' => 160000,
            'unit' => 'Unit',
        ],
        [
            'no' => 58,
            'description' => 'Router ZTE 670',
            'code' => '',
            'price' => 180000,
            'unit' => 'Unit',
        ],
        [
            'no' => 59,
            'description' => 'Router Totolink NRE200',
            'code' => '',
            'price' => 180000,
            'unit' => 'Unit',
        ],
        [
            'no' => 60,
            'description' => 'Router TP-LINK WR850',
            'code' => '',
            'price' => 180000,
            'unit' => 'Unit',
        ],
        [
            'no' => 61,
            'description' => 'Adaptor 5V',
            'code' => '',
            'price' => 15000,
            'unit' => 'Unit',
        ],
        [
            'no' => 62,
            'description' => 'Adaptor 9V',
            'code' => '',
            'price' => 15000,
            'unit' => 'Unit',
        ],
        [
            'no' => 63,
            'description' => 'Adaptor 12V',
            'code' => '',
            'price' => 15000,
            'unit' => 'Unit',
        ],
        [
            'no' => 64,
            'description' => 'Adaptor 24V',
            'code' => '',
            'price' => 32000,
            'unit' => 'Unit',
        ],
        [
            'no' => 65,
            'description' => 'POE 12V',
            'code' => '',
            'price' => 110000,
            'unit' => 'Unit',
        ],
        [
            'no' => 66,
            'description' => 'POE 24V',
            'code' => '',
            'price' => 120000,
            'unit' => 'Unit',
        ],
        [
            'no' => 67,
            'description' => 'HUBSWITCH 5Port',
            'code' => '',
            'price' => 75000,
            'unit' => 'Unit',
        ],
        [
            'no' => 68,
            'description' => 'HUBSWITCH 8Port',
            'code' => '',
            'price' => 120000,
            'unit' => 'Unit',
        ],
        [
            'no' => 69,
            'description' => 'HUBSWITCH 16Port',
            'code' => '',
            'price' => 900000,
            'unit' => 'Unit',
        ],
        [
            'no' => 70,
            'description' => 'Tiang T6 2\" Q1.6mm',
            'code' => '',
            'price' => 300000,
            'unit' => 'Batang',
        ],
        [
            'no' => 71,
            'description' => 'Tiang T6 2.5\" Q1.6mm',
            'code' => '',
            'price' => 400000,
            'unit' => 'Batang',
        ],
        [
            'no' => 72,
            'description' => 'Tiang T6 3\" Q1.6mm',
            'code' => '',
            'price' => 500000,
            'unit' => 'Batang',
        ],
        [
            'no' => 73,
            'description' => 'Tiang T7 3\" Q1.6mm',
            'code' => '',
            'price' => 650000,
            'unit' => 'Batang',
        ],
        [
            'no' => 74,
            'description' => 'Tiang T7 3\"-2.5\" Q1.6mm',
            'code' => '',
            'price' => 900000,
            'unit' => 'Batang',
        ],
        [
            'no' => 75,
            'description' => 'Tiang T6 2\" @2mm',
            'code' => '',
            'price' => 350000,
            'unit' => 'Batang',
        ],
        [
            'no' => 76,
            'description' => 'Tiang T6 2.5\" Q2mm',
            'code' => '',
            'price' => 450000,
            'unit' => 'Batang',
        ],
        [
            'no' => 77,
            'description' => 'Tiang T6 3\" Q2mm',
            'code' => '',
            'price' => 550000,
            'unit' => 'Batang',
        ],
        [
            'no' => 78,
            'description' => 'Tiang T7 3\" Q2mm',
            'code' => '',
            'price' => 700000,
            'unit' => 'Batang',
        ],
        [
            'no' => 79,
            'description' => 'Tiang T7 3\"-2.5\" Q2mm',
            'code' => '',
            'price' => 750000,
            'unit' => 'Batang',
        ],
        [
            'no' => 80,
            'description' => 'Tiang T7 4\"-3:-2.5\" Q2mm',
            'code' => '',
            'price' => 800000,
            'unit' => 'Batang',
        ],
        [
            'no' => 81,
            'description' => 'Slack suport 70CM',
            'code' => '',
            'price' => 120000,
            'unit' => 'Unit',
        ],
        [
            'no' => 82,
            'description' => 'Slack suport 60CM',
            'code' => '',
            'price' => 100000,
            'unit' => 'Unit',
        ],
        [
            'no' => 83,
            'description' => 'Slack suport 50CM',
            'code' => '',
            'price' => 70000,
            'unit' => 'Unit',
        ],
        [
            'no' => 84,
            'description' => 'Slack suport 40CM',
            'code' => '',
            'price' => 60000,
            'unit' => 'Unit',
        ],
        [
            'no' => 85,
            'description' => 'Pole Clamp Cincin',
            'code' => '',
            'price' => 14000,
            'unit' => 'Pcs',
        ],
        [
            'no' => 86,
            'description' => 'Pole Clamp Polos',
            'code' => '',
            'price' => 12000,
            'unit' => 'Pcs',
        ],
        [
            'no' => 87,
            'description' => 'Clamp Stainlist',
            'code' => '',
            'price' => 10000,
            'unit' => 'Pcs',
        ],
        [
            'no' => 88,
            'description' => 'Clamp Seng',
            'code' => '',
            'price' => 8000,
            'unit' => 'Pcs',
        ],
        [
            'no' => 89,
            'description' => 'Clamp A',
            'code' => '',
            'price' => 15000,
            'unit' => 'Pcs',
        ],
        [
            'no' => 90,
            'description' => 'Clamp Buaya 13mm',
            'code' => '',
            'price' => 21000,
            'unit' => 'Pcs',
        ],
        [
            'no' => 91,
            'description' => 'Clamp Buaya 9mm',
            'code' => '',
            'price' => 15000,
            'unit' => 'Pcs',
        ],
        [
            'no' => 92,
            'description' => 'Clamp Buaya 5mm',
            'code' => '',
            'price' => 4000,
            'unit' => 'Pcs',
        ],
        [
            'no' => 93,
            'description' => 'Helical 13mm',
            'code' => '',
            'price' => 15000,
            'unit' => 'Pcs',
        ],
        [
            'no' => 94,
            'description' => 'Helical 9mm',
            'code' => '',
            'price' => 14000,
            'unit' => 'Pcs',
        ],
        [
            'no' => 95,
            'description' => 'Helical 7mm',
            'code' => '',
            'price' => 11000,
            'unit' => 'Pcs',
        ],
        [
            'no' => 96,
            'description' => 'Helical 5mm',
            'code' => '',
            'price' => 10000,
            'unit' => 'Pcs',
        ],
        [
            'no' => 97,
            'description' => 'Tist 40cm',
            'code' => '',
            'price' => 600,
            'unit' => 'Pcs',
        ],
        [
            'no' => 98,
            'description' => 'Tist 35cm',
            'code' => '',
            'price' => 550,
            'unit' => 'Pcs',
        ],
        [
            'no' => 99,
            'description' => 'Tist 30cm',
            'code' => '',
            'price' => 500,
            'unit' => 'Pcs',
        ],
        [
            'no' => 100,
            'description' => 'Tist 25cm',
            'code' => '',
            'price' => 450,
            'unit' => 'Pcs',
        ],
        [
            'no' => 101,
            'description' => 'Tist 10cm',
            'code' => '',
            'price' => 300,
            'unit' => 'Pcs',
        ],
        [
            'no' => 102,
            'description' => 'Tist Kecil',
            'code' => '',
            'price' => 150,
            'unit' => 'Pcs',
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
     * Dapatkan semua item daftar harga material resmi 2026.
     */
    public static function allMaterials(): array
    {
        return self::MATERIALS;
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
     * Cari harga resmi material dari Sheet Materials berdasarkan nama/pola barang.
     */
    public static function findMaterialPrice(string $materialName): float
    {
        $name = strtolower(trim($materialName));
        if (empty($name)) {
            return 0;
        }

        // 1. Exact match
        foreach (self::MATERIALS as $m) {
            if (strtolower($m['description']) === $name && $m['price'] > 0) {
                return (float)$m['price'];
            }
        }

        // 2. Keyword matching yang akurat
        // Dropcore / DC 1C
        if ((str_contains($name, 'dc 1c') || str_contains($name, 'dc1c') || str_contains($name, 'dropcore') || str_contains($name, 'drop core')) && !str_contains($name, '2c') && !str_contains($name, '4c')) {
            return 750;
        }
        if (str_contains($name, 'dc 2c') || str_contains($name, 'dc2c')) return 900;
        if (str_contains($name, 'dc 4c') || str_contains($name, 'dc4c')) return 1400;

        // Fig8
        if (str_contains($name, 'fig8 2c') || str_contains($name, 'fig 8 2c')) return 2100;
        if (str_contains($name, 'fig8 4c') || str_contains($name, 'fig 8 4c')) return 2300;
        if (str_contains($name, 'fig8 6c') || str_contains($name, 'fig 8 6c')) return 2400;
        if (str_contains($name, 'fig8 8c') || str_contains($name, 'fig 8 8c')) return 2700;
        if (str_contains($name, 'fig8 12c') || str_contains($name, 'fig 8 12c')) return 3100;

        // Jelytube / JT
        if (str_contains($name, 'jelytube 2c') || str_contains($name, 'jt 2c')) return 1300;
        if (str_contains($name, 'jelytube 4c') || str_contains($name, 'jt 4c')) return 1800;

        // ADSS
        if (str_contains($name, 'adss 8c')) return 3500;
        if (str_contains($name, 'adss 12c')) return 3800;
        if (str_contains($name, 'adss 24c')) return 6500;

        // LAN
        if (str_contains($name, 'lan cat5') || str_contains($name, 'cat 5')) return 3000;
        if (str_contains($name, 'lan cat6') || str_contains($name, 'cat 6')) return 5000;

        // Fast Connector / Adapter
        if (str_contains($name, 'fast connector') || str_contains($name, 'fastconn')) return 5000;

        // Roset
        if (str_contains($name, 'roset')) return 6000;

        // Patchcord
        if (str_contains($name, 'patchcord') || str_contains($name, 'patchcore')) {
            if (str_contains($name, 'lc/upc') || str_contains($name, 'lc-upc')) return 50000;
            if (str_contains($name, 'hijau')) return 8000;
            return 7000;
        }

        // Pigtail
        if (str_contains($name, 'pigtail')) return 6000;

        // PreCo
        if (str_contains($name, 'preco')) return 25000;

        // HTB / Media Converter
        if (str_contains($name, 'htb 1c')) return 70000;
        if (str_contains($name, 'htb 2c')) return 150000;
        if (str_contains($name, 'htb 3c')) return 190000;
        if (str_contains($name, 'htb 4c')) return 310000;
        if (str_contains($name, 'htb 6c') || str_contains($name, 'htb')) return 350000;

        // Splitter
        if (str_contains($name, 'split') && str_contains($name, '1:2')) return 55000;
        if (str_contains($name, 'split') && str_contains($name, '1:4')) return 60000;
        if (str_contains($name, 'split') && str_contains($name, '1:8')) return 75000;
        if (str_contains($name, 'split') && str_contains($name, '1:16')) return 120000;

        // Box ODP
        if (str_contains($name, 'odp') && str_contains($name, '8c')) return 110000;
        if (str_contains($name, 'odp') && str_contains($name, '16c')) return 150000;
        if (str_contains($name, 'odp') && str_contains($name, '24c')) return 300000;

        // Closure
        if (str_contains($name, 'clos') || str_contains($name, 'clous')) {
            if (str_contains($name, '4c')) return 55000;
            if (str_contains($name, '8c')) return 65000;
            if (str_contains($name, '12c')) return 70000;
            if (str_contains($name, '48c')) return 450000;
            if (str_contains($name, '96c')) return 1200000;
        }

        // OTB
        if (str_contains($name, 'otb')) {
            if (str_contains($name, '12c')) return 65000;
            if (str_contains($name, '24c')) return 950000;
            if (str_contains($name, '48c')) return 1550000;
            if (str_contains($name, '96c')) return 2500000;
        }

        // Router / Modem / ONT
        if (str_contains($name, 'gm220')) return 140000;
        if (str_contains($name, 'huawei')) return 160000;
        if (str_contains($name, 'fiber home') || str_contains($name, 'fiberhome')) return 160000;
        if (str_contains($name, '660') || str_contains($name, 'f660')) return 160000;
        if (str_contains($name, '609') || str_contains($name, 'f609')) return 160000;
        if (str_contains($name, '670') || str_contains($name, 'f670') || str_contains($name, 'f672')) return 180000;
        if (str_contains($name, 'totolink')) return 180000;
        if (str_contains($name, 'wr850') || str_contains($name, 'tp-link')) return 180000;
        if (str_contains($name, 'modem') || str_contains($name, 'ont') || str_contains($name, 'router')) return 160000;

        // Adaptor
        if (str_contains($name, 'adaptor') || str_contains($name, 'adapter')) {
            if (str_contains($name, '24v')) return 32000;
            return 15000;
        }

        // POE
        if (str_contains($name, 'poe 12v')) return 110000;
        if (str_contains($name, 'poe 24v') || str_contains($name, 'poe')) return 120000;

        // Switch Hub
        if (str_contains($name, 'switch') || str_contains($name, 'hub')) {
            if (str_contains($name, '16')) return 900000;
            if (str_contains($name, '8')) return 120000;
            return 75000;
        }

        // Clamp
        if (str_contains($name, 'pole clamp cincin')) return 14000;
        if (str_contains($name, 'pole clamp')) return 12000;
        if (str_contains($name, 'clamp stainlist') || str_contains($name, 'stainless')) return 10000;
        if (str_contains($name, 'clamp seng')) return 8000;
        if (str_contains($name, 'clamp a')) return 15000;
        if (str_contains($name, 'buaya 13')) return 21000;
        if (str_contains($name, 'buaya 9')) return 15000;
        if (str_contains($name, 'buaya') || str_contains($name, 'clamp')) return 4000;

        // Helical
        if (str_contains($name, 'helical 13')) return 15000;
        if (str_contains($name, 'helical 9')) return 14000;
        if (str_contains($name, 'helical 7')) return 11000;
        if (str_contains($name, 'helical')) return 10000;

        // Tiang
        if (str_contains($name, 'tiang')) {
            if (str_contains($name, 't7')) return 750000;
            if (str_contains($name, 't6')) return 400000;
            return 350000;
        }

        // Ties
        if (str_contains($name, 'tie') || str_contains($name, 'tist')) {
            if (str_contains($name, '40')) return 600;
            if (str_contains($name, '35')) return 550;
            if (str_contains($name, '30')) return 500;
            if (str_contains($name, '25')) return 450;
            if (str_contains($name, '10')) return 300;
            return 150;
        }

        // Substring scan fallback from MATERIALS list
        foreach (self::MATERIALS as $m) {
            $mDesc = strtolower($m['description']);
            if ($m['price'] > 0 && (str_contains($name, $mDesc) || str_contains($mDesc, $name))) {
                return (float)$m['price'];
            }
        }

        return 0;
    }

    /**
     * Format Rupiah helper
     */
    public static function formatRupiah(float $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}