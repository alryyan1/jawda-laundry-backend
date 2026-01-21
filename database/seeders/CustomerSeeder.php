<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\User;


class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $staffUser = User::first();
        // Create default customer
        Customer::firstOrCreate(
            ['email' => 'default@laundry.com'],
            [
                'name' => 'Default Customer',
                'phone' => '000-0000',
                'address' => 'Default Address',
                'user_id' => $staffUser?->id,
                'notes' => 'Default customer for system operations.',
                'is_default' => true,
            ]
        );
    }
}
