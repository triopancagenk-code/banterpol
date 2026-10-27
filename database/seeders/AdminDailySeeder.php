<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminDailySeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admindaily'],
            [
                'name' => 'Budi Setiawan',
                'phone' => '081234567890',
                'role' => 'technician',
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('admindaily'),
            ]
        );
    }
}
