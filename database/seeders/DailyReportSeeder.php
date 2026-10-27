<?php

namespace Database\Seeders;

use App\Models\DailyReport;
use App\Models\User;
use Illuminate\Database\Seeder;

class DailyReportSeeder extends Seeder
{
    public function run(): void
    {
        if (DailyReport::count() > 0) {
            return;
        }

        $tech = User::where('role', 'technician')->first() ?: User::first();

        DailyReport::create([
            'report_date' => now()->toDateString(),
            'technician_name' => 'Budi Setiawan',
            'technician_id' => $tech?->id,
            'partner_company' => 'PT NetMedia Solusi Nusantara',
            'job_type' => 'Pasang Baru (PSB / Installation)',
            'customer_name' => 'Bpk Herman / CST-109',
            'customer_phone' => '081234567890',
            'customer_address' => 'Jl. Merdeka No. 45, Purwokerto',
            'pppoe_username' => 'herman_net',
            'odp_port' => 'ODP-TMB-04 / Port 2',
            'rx_power' => '-19.5',
            'ont_sn' => 'ZTEGC819A2B',
            'materials_json' => [
                ['name' => 'Kabel Dropcore', 'price' => 2500, 'qty' => 85, 'unit' => 'm', 'total' => 212500],
                ['name' => 'Fast Connector', 'price' => 7500, 'qty' => 2, 'unit' => 'pcs', 'total' => 15000],
                ['name' => 'Roset FO', 'price' => 12000, 'qty' => 1, 'unit' => 'pcs', 'total' => 12000],
            ],
            'material_total_cost' => 239500,
            'transaction_type' => 'Biaya Pasang Baru (PSB)',
            'cash_amount' => 250000,
            'payment_method' => 'Tunai (Cash)',
            'cash_deposit_status' => 'Belum Disetor',
            'synced_to_sheets' => false,
            'notes' => 'Pemasangan lancar, redaman aman.',
        ]);

        DailyReport::create([
            'report_date' => now()->toDateString(),
            'technician_name' => 'Aji (Teknisi Lapangan)',
            'technician_id' => $tech?->id,
            'partner_company' => 'PT NetMedia Solusi Nusantara',
            'job_type' => 'Maintenance / Perbaikan Gangguan',
            'customer_name' => 'Ibu Siti Rahma / CST-088',
            'customer_phone' => '081398765432',
            'customer_address' => 'Jl. Raya Sidamakmur No. 12, Banyumas',
            'pppoe_username' => 'siti_rahma',
            'odp_port' => 'ODP-CLK-02 / Port 4',
            'rx_power' => '-18.2',
            'ont_sn' => 'ZTEGC1234567',
            'materials_json' => [
                ['name' => 'Fast Connector', 'price' => 7500, 'qty' => 2, 'unit' => 'pcs', 'total' => 15000],
                ['name' => 'Patchcord FO', 'price' => 15000, 'qty' => 1, 'unit' => 'pcs', 'total' => 15000],
                ['name' => 'PLC Splitter 1:2', 'price' => 16000, 'qty' => 1, 'unit' => 'pcs', 'total' => 16000],
            ],
            'material_total_cost' => 46000,
            'transaction_type' => 'Tidak Ada Transaksi Kas',
            'cash_amount' => 0,
            'payment_method' => 'Non-Tunai',
            'cash_deposit_status' => 'Non-Tunai',
            'synced_to_sheets' => false,
            'notes' => 'Perbaikan redaman loss ODP selesai.',
        ]);
    }
}
