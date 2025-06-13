<?php
namespace Database\Factories;

use App\Models\CustomerType;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'notes' => fake()->boolean(25) ? fake()->sentence() : null, // 25% chance of having notes
            'customer_type_id' => CustomerType::inRandomOrder()->first()?->id, // Assign a random existing customer type
            // 'user_id' => User::where('role', 'staff')->inRandomOrder()->first()?->id, // If staff user should be random
        ];
    }
}