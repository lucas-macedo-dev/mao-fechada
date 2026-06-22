<?php

namespace App\Services;

use App\Models\User;

class DefaultCategorySeeder
{
    public static function categories(string $locale): array
    {
        if ($locale === 'pt-BR') {
            return [
                ['name' => 'Veículo', 'type' => 'expense'],
                ['name' => 'Habitação', 'type' => 'expense'],
                ['name' => 'Alimentação', 'type' => 'expense'],
                ['name' => 'Compras', 'type' => 'expense'],
                ['name' => 'Lazer', 'type' => 'expense'],
                ['name' => 'Salário', 'type' => 'income'],
                ['name' => 'Investimentos', 'type' => 'income'],
                ['name' => 'Ticket Alimentação', 'type' => 'income'],
            ];
        }

        return [
            ['name' => 'Vehicle', 'type' => 'expense'],
            ['name' => 'Housing', 'type' => 'expense'],
            ['name' => 'Food', 'type' => 'expense'],
            ['name' => 'Shopping', 'type' => 'expense'],
            ['name' => 'Leisure', 'type' => 'expense'],
            ['name' => 'Salary', 'type' => 'income'],
            ['name' => 'Investments', 'type' => 'income'],
            ['name' => 'Food Vouchers', 'type' => 'income'],
        ];
    }

    public function seedFor(User $user): void
    {
        if ($user->categories()->doesntExist()) {
            $user->categories()->createMany(
                self::categories($user->locale ?? 'en')
            );
        }
    }
}
