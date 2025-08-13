<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'daily_order_number' => $this->faker->unique()->numberBetween(1, 9999),
            'customer_id' => Customer::factory(),
            'user_id' => User::factory(),
            'status' => $this->faker->randomElement(['pending', 'processing', 'delivered', 'completed', 'cancelled']),
            'total_amount' => $this->faker->randomFloat(2, 10, 1000),
            'paid_amount' => $this->faker->randomFloat(2, 0, 1000),
            'payment_status' => $this->faker->randomElement(['pending', 'paid', 'partially_paid']),
            'payment_method' => $this->faker->randomElement(['cash', 'card', 'online']),
            'notes' => $this->faker->optional()->sentence(),
            'order_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'due_date' => $this->faker->optional()->dateTimeBetween('now', '+7 days'),
            'pickup_date' => $this->faker->optional()->dateTimeBetween('-7 days', 'now'),
            'delivered_date' => $this->faker->optional()->dateTimeBetween('-7 days', 'now'),
            'order_complete' => $this->faker->boolean(),
            'whatsapp_text_sent' => $this->faker->boolean(),
            'whatsapp_pdf_sent' => $this->faker->boolean(),
        ];
    }
}
