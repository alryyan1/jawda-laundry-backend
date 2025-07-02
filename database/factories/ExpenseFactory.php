<?php
namespace Database\Factories;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        // Use the new model relationship
        $category = ExpenseCategory::inRandomOrder()->first();
        $user = User::whereHas('roles', fn($q) => $q->whereIn('name', ['admin', 'receptionist']))->inRandomOrder()->first();

        return [
            'name' => ucfirst(fake()->words(rand(2,4), true)),
            'expense_category_id' => $category?->id, // Assign the foreign key
            'description' => fake()->boolean(40) ? fake()->sentence() : null,
            'amount' => fake()->randomFloat(2, 10, 800), // expenses between $10 and $800
            'expense_date' => fake()->dateTimeBetween('-2 years', 'now'),
            'user_id' => $user?->id, // User who recorded it
        ];
    }
}