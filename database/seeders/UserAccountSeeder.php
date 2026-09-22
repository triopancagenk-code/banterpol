<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserAccountSeeder extends Seeder
{
    public function run(): void
    {
        // Teknisi baru
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

        // Kolektor baru
        $collectors = [
            [
                'email' => 'dila@kolektor.net',
                'name' => 'Dila (Kolektor Lapangan)',
                'phone' => '081234567101',
                'password' => 'dilakolektor',
            ],
            [
                'email' => 'saefudin@kolektor.net',
                'name' => 'Saefudin (Kolektor Lapangan)',
                'phone' => '081234567102',
                'password' => 'saefudinkolektor',
            ],
            [
                'email' => 'arti@kolektor.net',
                'name' => 'Arti (Kolektor Lapangan)',
                'phone' => '081234567103',
                'password' => 'artikolektor',
            ],
            [
                'email' => 'aji@kolektor.net',
                'name' => 'Aji (Kolektor Lapangan)',
                'phone' => '081234567104',
                'password' => 'ajikolektor',
            ],
            [
                'email' => 'bagas@kolektor.net',
                'name' => 'Bagas (Kolektor Lapangan)',
                'phone' => '081234567105',
                'password' => 'bagaskolektor',
            ],
        ];

        foreach ($collectors as $c) {
            User::updateOrCreate(
                ['email' => $c['email']],
                [
                    'name' => $c['name'],
                    'phone' => $c['phone'],
                    'role' => 'collector',
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'password' => Hash::make($c['password']),
                ]
            );
        }
    }
}
