<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CustomerType;

class CustomerTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Individual', 'description' => 'Standard individual customer.'],
            ['name' => 'Corporate', 'description' => 'Business or company account.'],
            ['name' => 'VIP', 'description' => 'Special customer with premium benefits.'],
        ];

        foreach ($types as $type) {
            CustomerType::firstOrCreate(['name' => $type['name']], $type);
        }
    }
}