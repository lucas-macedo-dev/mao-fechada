<?php

use App\Services\DefaultCategorySeeder;

it('returns Portuguese category names for pt-BR locale', function () {
    $categories = DefaultCategorySeeder::categories('pt-BR');

    $names = array_column($categories, 'name');

    expect($names)->toContain('Veículo', 'Habitação', 'Alimentação', 'Compras', 'Lazer');
    expect($names)->toContain('Salário', 'Investimentos', 'Ticket Alimentação');
    expect(array_count_values($names)['Sem Categoria'])->toBe(2);
});

it('returns English category names for en locale', function () {
    $categories = DefaultCategorySeeder::categories('en');

    $names = array_column($categories, 'name');

    expect($names)->toContain('Vehicle', 'Housing', 'Food', 'Shopping', 'Leisure');
    expect($names)->toContain('Salary', 'Investments', 'Food Vouchers');
    expect(array_count_values($names)['No Category'])->toBe(2);
});

it('returns 6 expense and 4 income categories for any locale', function () {
    foreach (['pt-BR', 'en'] as $locale) {
        $categories = DefaultCategorySeeder::categories($locale);

        $expenses = array_filter($categories, fn ($c) => $c['type'] === 'expense');
        $incomes = array_filter($categories, fn ($c) => $c['type'] === 'income');

        expect(count($expenses))->toBe(6);
        expect(count($incomes))->toBe(4);
    }
});
