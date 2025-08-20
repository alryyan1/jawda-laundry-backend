<?php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'car_plate_number' => fake()->optional()->regexify('[A-Z]{2,3}[0-9]{3,4}'),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->optional()->safeEmail(),
            'address' => fake()->optional()->address(),
            'notes' => fake()->optional()->sentence(),
            'user_id' => User::inRandomOrder()->first()?->id, // Assign to a random existing user
            'is_default' => false,
        ];
    }
}