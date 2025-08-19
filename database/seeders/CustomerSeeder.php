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
                'phone' => '000-0000',
                'address' => 'Default Address',
                'user_id' => $staffUser?->id,
                'customer_type_id' => $individualType?->id,
                'notes' => 'Default customer for system operations.',
                'is_default' => true,
            ]
        );


    }
}