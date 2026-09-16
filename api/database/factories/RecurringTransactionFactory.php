<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\RecurringTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecurringTransaction>
 */
class RecurringTransactionFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement(['income', 'expense']);

        return [
            'user_id'           => User::factory(),
            'category_id'       => Category::factory()->state(['type' => $type]),
            'type'              => $type,
            'payment_method'    => fake()->randomElement([
                'credit_card',
                'debit_card',
                'cash',
                'pix',
                'bank_slip',
                'bank_transfer',
            ]),
            'amount'            => fake()->randomFloat(2, 1, 5000),
            'notes'             => fake()->boolean(50) ? fake()->sentence() : null,
            'day_of_month'      => fake()->numberBetween(1, 28),
            'status'            => 'active',
            'last_generated_at' => null,
        ];
    }
}
