<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\InventoryCategory;

class InventoryCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Detergents & Soaps',
                'description' => 'Laundry detergents, fabric softeners, and cleaning soaps',
                'is_active' => true
            ],
            [
                'name' => 'Bleach & Stain Removers',
                'description' => 'Bleach, stain removers, and whitening agents',
                'is_active' => true
            ],
            [
                'name' => 'Fabric Care',
                'description' => 'Fabric softeners, starch, and fabric conditioners',
                'is_active' => true
            ],
            [
                'name' => 'Packaging Materials',
                'description' => 'Plastic bags, hangers, and packaging supplies',
                'is_active' => true
            ],
            [
                'name' => 'Equipment & Tools',
                'description' => 'Maintenance supplies and cleaning tools',
                'is_active' => true
            ],
            [
                'name' => 'Office Supplies',
                'description' => 'Paper, pens, and administrative supplies',
                'is_active' => true
            ]
        ];

        foreach ($categories as $category) {
            InventoryCategory::create($category);
        }
    }
}
