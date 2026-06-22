<?php

use App\Services\DefaultCategorySeeder;

it('returns Portuguese category names for pt-BR locale', function () {
    $categories = DefaultCategorySeeder::categories('pt-BR');

    $names = array_column($categories, 'name');

    expect($names)->toContain('Veículo', 'Habitação', 'Alimentação', 'Compras', 'Lazer');
    expect($names)->toContain('Salário', 'Investimentos', 'Ticket Alimentação');
});

it('returns English category names for en locale', function () {
    $categories = DefaultCategorySeeder::categories('en');

    $names = array_column($categories, 'name');

    expect($names)->toContain('Vehicle', 'Housing', 'Food', 'Shopping', 'Leisure');
    expect($names)->toContain('Salary', 'Investments', 'Food Vouchers');
});

it('returns 5 expense and 3 income categories for any locale', function () {
    foreach (['pt-BR', 'en'] as $locale) {
        $categories = DefaultCategorySeeder::categories($locale);

        $expenses = array_filter($categories, fn ($c) => $c['type'] === 'expense');
        $incomes = array_filter($categories, fn ($c) => $c['type'] === 'income');

        expect(count($expenses))->toBe(5);
        expect(count($incomes))->toBe(3);
    }
});
