<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder creates the primary admin and a default staff user.
     * It uses firstOrCreate to prevent creating duplicates if the seeder is run multiple times.
     */
    public function run(): void
    {
        // --- Create Admin User ---
        // This user will have full super-admin access.
        User::firstOrCreate(
            ['username' => 'admin'], // Use username as the unique key for checking existence
            [
                'name' => 'Admin User',
                'email' => 'admin@admin.com',
                'email_verified_at' => now(),
                'password' => Hash::make('12345678'), // IMPORTANT: Change this for production!
                'remember_token' => Str::random(10),
            ]
        );
        $this->command->info('Admin user created or verified.');


        // --- Create a Default Staff User ---
        // This user will be assigned the 'receptionist' role by default in the PermissionSeeder.
        User::firstOrCreate(
            ['username' => 'staff'], // Use username as the unique key
            [
                'name' => 'Staff Receptionist',
                'email' => 'staff@staff.com',
                'email_verified_at' => now(),
                'password' => Hash::make('12345678'),
                'remember_token' => Str::random(10),
            ]
        );
        $this->command->info('Default staff user created or verified.');
    }
}