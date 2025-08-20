<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\User;
use App\Models\CustomerType;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $staffUser = User::first();
        $individualType = CustomerType::where('name', 'Individual')->first();

        if (!$staffUser || !$individualType) {
            $this->command->warn('Staff user or Individual customer type not found. Skipping some customer seeds.');
            // return; // Or handle differently
        }

        // Create default customer
        Customer::firstOrCreate(
            ['email' => 'default@restaurant.com'],
            [
                'name' => 'Default Customer',
                'car_plate_number' => null,
                'phone' => '+1234567890',
                'email' => 'default@restaurant.com',
                'address' => 'Default Address',
                'notes' => 'Default customer for system operations',
                'is_default' => true,
            ]
        );


    }
}