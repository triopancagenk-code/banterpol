<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Akun Pengguna (Admin, Pelanggan, Teknisi, Kolektor)
        User::updateOrCreate(
            ['email' => 'admin@banterpool.net'],
            [
                'name' => 'Administrator Banterpool',
                'phone' => '081234567890',
                'role' => 'admin',
                'is_active' => true,
                'password' => Hash::make('admin'),
            ]
        );

        // Akun Direktur (Akses Administrator Eksekutif)
        User::updateOrCreate(
            ['email' => 'direktur@banterpool.net'],
            [
                'name' => 'Direktur Utama Banterpool',
                'phone' => '081234567899',
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('direktur'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'root@gmail.com'],
            [
                'name' => 'Pelanggan Root',
                'phone' => '081234567891',
                'role' => 'customer',
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('root270094'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'mamat@teknisi.net'],
            [
                'name' => 'Mamat (Teknisi Lapangan)',
                'phone' => '081234567001',
                'role' => 'technician',
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('mamatteknisi'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'aji@teknisi.net'],
            [
                'name' => 'Aji (Teknisi Lapangan)',
                'phone' => '081234567002',
                'role' => 'technician',
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('ajiteknisi'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'danu@teknisi.net'],
            [
                'name' => 'Danu (Teknisi Lapangan)',
                'phone' => '081234567003',
                'role' => 'technician',
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('danuteknisi'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'okta@teknisi.net'],
            [
                'name' => 'Okta (Teknisi Lapangan)',
                'phone' => '081234567004',
                'role' => 'technician',
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('oktateknisi'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'dila@kolektor.net'],
            [
                'name' => 'Dila (Kolektor Lapangan)',
                'phone' => '081234567101',
                'role' => 'collector',
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('dilakolektor'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'saefudin@kolektor.net'],
            [
                'name' => 'Saefudin (Kolektor Lapangan)',
                'phone' => '081234567102',
                'role' => 'collector',
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('saefudinkolektor'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'arti@kolektor.net'],
            [
                'name' => 'Arti (Kolektor Lapangan)',
                'phone' => '081234567103',
                'role' => 'collector',
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('artikolektor'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'aji@kolektor.net'],
            [
                'name' => 'Aji (Kolektor Lapangan)',
                'phone' => '081234567104',
                'role' => 'collector',
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('ajikolektor'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'bagas@kolektor.net'],
            [
                'name' => 'Bagas (Kolektor Lapangan)',
                'phone' => '081234567105',
                'role' => 'collector',
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('bagaskolektor'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'phone' => '089876543210',
                'role' => 'customer',
                'is_active' => true,
                'password' => Hash::make('password'),
            ]
        );

        // 2. Sinkronisasi Master Paket Berlangganan (Sesuai POV Pelanggan)
        $packages = [
            [
                'name' => 'Paket 20 Mbps',
                'speed' => '20 Mbps',
                'price' => 110000,
                'description' => 'Kecepatan 20 Mbps, Untuk Penggunaan Ringan & Keluarga Kecil, 3 - 5 Perangkat, Support Prioritas',
                'is_active' => true,
            ],
            [
                'name' => 'Paket 30 Mbps',
                'speed' => '30 Mbps',
                'price' => 165000,
                'description' => 'Kecepatan 30 Mbps, Untuk Kebutuhan Keluarga, 5 - 7 Perangkat, Support Prioritas',
                'is_active' => true,
            ],
            [
                'name' => 'Paket 50 Mbps',
                'speed' => '50 Mbps',
                'price' => 220000,
                'description' => 'Kecepatan 50 Mbps, Streaming & Gaming Lancar, 7 - 10 Perangkat, Support 24/7',
                'is_active' => true,
            ],
        ];

        foreach ($packages as $pkg) {
            Package::updateOrCreate(
                ['name' => $pkg['name']],
                $pkg
            );
        }

        // 3. Impor seluruh data pelanggan dari berkas pendaftaran Google Drive Banterpool
        $this->call(CustomerDriveSeeder::class);
    }
}
