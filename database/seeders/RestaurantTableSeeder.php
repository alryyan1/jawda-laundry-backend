<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RestaurantTable;

class RestaurantTableSeeder extends Seeder
{
    public function run(): void
    {
        $tables = [
            [
                'name' => 'Table 1',
                'number' => 'T1',
                'capacity' => 4,
                'description' => 'Indoor table near the window',
                'status' => 'available',
                'is_active' => true,
            ],
            [
                'name' => 'Table 2',
                'number' => 'T2',
                'capacity' => 4,
                'description' => 'Indoor table in the center',
                'status' => 'available',
                'is_active' => true,
            ],
            [
                'name' => 'Table 3',
                'number' => 'T3',
                'capacity' => 6,
                'description' => 'Large table for groups',
                'status' => 'available',
                'is_active' => true,
            ],
            [
                'name' => 'VIP Table 1',
                'number' => 'VIP1',
                'capacity' => 4,
                'description' => 'Premium table with better view',
                'status' => 'available',
                'is_active' => true,
            ],
            [
                'name' => 'Outdoor Table 1',
                'number' => 'OUT1',
                'capacity' => 4,
                'description' => 'Outdoor seating area',
                'status' => 'available',
                'is_active' => true,
            ],
            [
                'name' => 'Outdoor Table 2',
                'number' => 'OUT2',
                'capacity' => 6,
                'description' => 'Large outdoor table',
                'status' => 'available',
                'is_active' => true,
            ],
        ];

        foreach ($tables as $table) {
            RestaurantTable::firstOrCreate(
                ['number' => $table['number']],
                $table
            );
        }
    }
} 