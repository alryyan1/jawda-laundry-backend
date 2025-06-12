<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\User;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $staffUser = User::where('email', 'staff@laundry.com')->first();

        Customer::firstOrCreate(
            ['email' => 'john.doe@example.com'],
            [
                'name' => 'John Doe',
                'phone' => '555-0100',
                'address' => '123 Main St, Anytown, USA',
                'user_id' => $staffUser?->id, // Optional: staff who added them
            ]
        );

        Customer::firstOrCreate(
            ['email' => 'jane.smith@example.com'],
            [
                'name' => 'Jane Smith',
                'phone' => '555-0101',
                'address' => '456 Oak Ave, Anytown, USA',
                'user_id' => $staffUser?->id,
            ]
        );
         Customer::factory()->count(10)->create(['user_id' => $staffUser?->id]); // Using factory
    }
}