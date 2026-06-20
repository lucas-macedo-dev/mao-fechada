<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement(['income', 'expense']);

        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory()->state(['type' => $type]),
            'type' => $type,
            'payment_method' => fake()->randomElement([
                'cartao_credito',
                'cartao_debito',
                'dinheiro',
                'pix',
                'boleto',
                'ted',
            ]),
            'amount' => fake()->randomFloat(2, 1, 5000),
            'transacted_at' => fake()->date(),
            'notes' => fake()->boolean(50) ? fake()->sentence() : null,
        ];
    }
}
