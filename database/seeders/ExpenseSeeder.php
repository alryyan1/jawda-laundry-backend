<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;

class ExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // This seeder is primarily for development/testing.
        // It's often better to combine this logic into a larger TestSystemSeeder
        // that only runs in non-production environments.

        if (app()->environment('local', 'development')) {
            $this->command->info('Seeding expenses for development environment...');

            // Ensure we have categories and users to assign
            if (ExpenseCategory::count() === 0) {
                $this->call(ExpenseCategorySeeder::class);
            }
            if (User::count() < 3) { // Ensure there are some users to assign expenses to
                 $this->command->warn('Not enough users found. Please run UserSeeder. Skipping expense seeding.');
                 return;
            }

            // Create 50 random expenses if the table is empty
            if (Expense::count() === 0) {
                Expense::factory()->count(50)->create();
                $this->command->info('50 random expenses created.');
            } else {
                $this->command->info('Expenses table is not empty, skipping factory creation.');
            }
        }
    }
}