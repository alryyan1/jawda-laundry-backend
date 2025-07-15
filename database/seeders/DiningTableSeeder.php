<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DiningTable;

class DiningTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tables = [
            ['name' => 'Table 1', 'capacity' => 4, 'description' => 'Window table'],
            ['name' => 'Table 2', 'capacity' => 4, 'description' => 'Window table'],
            ['name' => 'Table 3', 'capacity' => 6, 'description' => 'Center table'],
            ['name' => 'Table 4', 'capacity' => 6, 'description' => 'Center table'],
            ['name' => 'Table 5', 'capacity' => 2, 'description' => 'Intimate table'],
            ['name' => 'Table 6', 'capacity' => 2, 'description' => 'Intimate table'],
            ['name' => 'Table 7', 'capacity' => 8, 'description' => 'Large group table'],
            ['name' => 'Table 8', 'capacity' => 4, 'description' => 'Garden view'],
        ];

        foreach ($tables as $table) {
            DiningTable::create($table);
        }
    }
} 