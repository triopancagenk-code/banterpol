<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
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
    }
}
