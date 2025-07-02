<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Supplier>
 */
class SupplierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' ' . fake()->randomElement(['Supplies', 'Chemicals', 'Distributors']),
            'contact_person' => fake()->boolean(80) ? fake()->name() : null, // 80% chance of having a contact person
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->companyEmail(),
            'address' => fake()->address(),
            'notes' => fake()->boolean(25) ? fake()->paragraph(1) : null, // 25% chance of having notes
        ];
    }
}