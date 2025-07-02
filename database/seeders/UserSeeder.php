<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@laundry.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'), // Change in production!
            ]
        );

        User::firstOrCreate(
            ['email' => 'staff@laundry.com'],
            [
                'name' => 'Staff User',
                'password' => Hash::make('password'),
            ]
        );
    }
}