<?php

namespace App\Services;

use App\Models\User;

class DefaultCategorySeeder
{
    public static function categories(string $locale): array
    {
        if ($locale === 'pt-BR') {
            return [
                ['name' => 'Veículo', 'type' => 'expense', 'icon' => 'fa-solid fa-car-side'],
                ['name' => 'Habitação', 'type' => 'expense', 'icon' => 'fa-solid fa-house'],
                ['name' => 'Alimentação', 'type' => 'expense', 'icon' => 'fa-solid fa-utensils'],
                ['name' => 'Compras', 'type' => 'expense', 'icon' => 'fa-solid fa-cart-shopping'],
                ['name' => 'Lazer', 'type' => 'expense', 'icon' => 'fa-solid fa-film'],
                ['name' => 'Salário', 'type' => 'income', 'icon' => 'fa-solid fa-hand-holding-dollar'],
                ['name' => 'Investimentos', 'type' => 'income'],
                ['name' => 'Ticket Alimentação', 'type' => 'income', 'icon' => 'fa-solid fa-utensils'],
                ['name' => 'Sem Categoria', 'type' => 'expense'],
                ['name' => 'Sem Categoria', 'type' => 'income'],
            ];
        }

        return [
            ['name' => 'Vehicle', 'type' => 'expense', 'icon' => 'fa-solid fa-car-side'],
            ['name' => 'Housing', 'type' => 'expense', 'icon' => 'fa-solid fa-house'],
            ['name' => 'Food', 'type' => 'expense', 'icon' => 'fa-solid fa-utensils'],
            ['name' => 'Shopping', 'type' => 'expense', 'icon' => 'fa-solid fa-cart-shopping'],
            ['name' => 'Leisure', 'type' => 'expense', 'icon' => 'fa-solid fa-film'],
            ['name' => 'Salary', 'type' => 'income', 'icon' => 'fa-solid fa-hand-holding-dollar'],
            ['name' => 'Investments', 'type' => 'income'],
            ['name' => 'Food Vouchers', 'type' => 'income', 'icon' => 'fa-solid fa-utensils'],
            ['name' => 'No Category', 'type' => 'expense'],
            ['name' => 'No Category', 'type' => 'income'],
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
