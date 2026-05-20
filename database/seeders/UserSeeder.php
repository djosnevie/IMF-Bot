<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@bisoubisou.com'],
            [
                'name' => 'Admin Bisou Bisou',
                'password' => Hash::make('admin123'),
            ]
        );

        $admin->assignRole('super-admin');
    }
}
