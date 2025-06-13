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
        $staffUser = User::where('role', 'staff')->first();
        $individualType = CustomerType::where('name', 'Individual')->first();
        $corporateType = CustomerType::where('name', 'Corporate')->first();

        if (!$staffUser || !$individualType) {
            $this->command->warn('Staff user or Individual customer type not found. Skipping some customer seeds.');
            // return; // Or handle differently
        }

        Customer::firstOrCreate(
            ['email' => 'john.doe@example.com'],
            [
                'name' => 'John Doe',
                'phone' => '555-0100',
                'address' => '123 Main St, Anytown, USA',
                'user_id' => $staffUser?->id,
                'customer_type_id' => $individualType?->id,
                'notes' => 'Regular customer, prefers starch on shirts.',
            ]
        );

        Customer::firstOrCreate(
            ['email' => 'acme.corp@example.com'],
            [
                'name' => 'Acme Corp',
                'phone' => '555-0200',
                'address' => '789 Business Rd, Anytown, USA',
                'user_id' => $staffUser?->id,
                'customer_type_id' => $corporateType?->id,
                'notes' => 'Monthly billing, contact person: Jane Smith.',
            ]
        );

        // Create more customers using a factory
        if (Customer::count() < 10) { // Only create if not many exist
             Customer::factory()->count(8)
                ->sequence(
                    fn ($sequence) => ['customer_type_id' => ($sequence->index % 2 == 0) ? $individualType?->id : $corporateType?->id]
                )
                ->create(['user_id' => $staffUser?->id]);
        }
    }
}