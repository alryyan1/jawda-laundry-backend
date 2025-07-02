<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ExpenseCategory;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding expense categories...');

        $categories = [
            ['name' => 'Utilities', 'description' => 'Monthly bills like electricity, water, and internet.'],
            ['name' => 'Supplies', 'description' => 'Consumables like detergents, softeners, hangers, and packaging.'],
            ['name' => 'Rent', 'description' => 'Monthly or annual rent for the business premises.'],
            ['name' => 'Salaries', 'description' => 'Employee wages and salaries.'],
            ['name' => 'Maintenance', 'description' => 'Repair and maintenance costs for washing machines, dryers, etc.'],
            ['name' => 'Marketing', 'description' => 'Costs associated with advertising and promotion.'],
            ['name' => 'Miscellaneous', 'description' => 'Other uncategorized business expenses.'],
        ];

        foreach ($categories as $category) {
            // Use firstOrCreate to avoid creating duplicates if the seeder is run multiple times
            ExpenseCategory::firstOrCreate(['name' => $category['name']], $category);
        }

        $this->command->info('Expense categories seeded successfully.');
    }
}