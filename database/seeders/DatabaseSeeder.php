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
            ['email' => 'teknisi@banterpool.net'],
            [
                'name' => 'Randi Pratama (Teknisi Lapangan)',
                'phone' => '081234567888',
                'role' => 'technician',
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'kolektor@banterpool.net'],
            [
                'name' => 'Bayu Saputra (Kolektor Lapangan)',
                'phone' => '081234567777',
                'role' => 'collector',
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
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
