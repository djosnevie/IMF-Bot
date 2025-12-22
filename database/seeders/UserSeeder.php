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
        User::create([
            'name' => 'Admin Bisou Bisou',
            'email' => 'admin@bisoubisou.com',
            'password' => Hash::make('admin123'),
        ]);
    }
}
