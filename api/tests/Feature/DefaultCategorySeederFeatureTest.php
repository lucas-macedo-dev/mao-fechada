<?php

use App\Models\Category;
use App\Models\User;
use App\Services\DefaultCategorySeeder;
use Illuminate\Support\Facades\Artisan;

it('does not seed categories when user already has at least one category', function () {
    $user = User::factory()->create(['locale' => 'en']);
    $seeder = new DefaultCategorySeeder();

    $seeder->seedFor($user);
    $countAfterFirst = $user->categories()->count();

    $seeder->seedFor($user);
    $countAfterSecond = $user->categories()->count();

    expect($countAfterFirst)->toBe(8);
    expect($countAfterSecond)->toBe(8);
});

it('creates 8 default categories after registration', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Test User',
        'email' => 'newuser@example.com',
        'password' => 'password1!',
        'locale' => 'en',
    ]);

    $response->assertCreated();

    $user = User::where('email', 'newuser@example.com')->firstOrFail();

    expect($user->categories()->count())->toBe(8);
    expect($user->categories()->where('type', 'expense')->count())->toBe(5);
    expect($user->categories()->where('type', 'income')->count())->toBe(3);
});

it('creates pt-BR categories when locale is pt-BR', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Usuário Teste',
        'email' => 'usuario@example.com',
        'password' => 'senha1234!',
        'locale' => 'pt-BR',
    ]);

    $response->assertCreated();

    $user = User::where('email', 'usuario@example.com')->firstOrFail();

    expect($user->categories()->pluck('name'))->toContain('Veículo', 'Salário');
});

it('backfill command seeds only users with no categories', function () {
    $userWithoutCategories1 = User::factory()->create(['locale' => 'en']);
    $userWithoutCategories2 = User::factory()->create(['locale' => 'pt-BR']);
    $userWithCategories = User::factory()->create(['locale' => 'en']);

    Category::factory()->for($userWithCategories)->create(['type' => 'expense']);

    Artisan::call('categories:seed-defaults');
    $output = Artisan::output();

    expect($output)->toContain('Seeded 2 users');
    expect($output)->toContain('Skipped 1 users');

    expect($userWithoutCategories1->fresh()->categories()->count())->toBe(8);
    expect($userWithoutCategories2->fresh()->categories()->count())->toBe(8);
    expect($userWithCategories->fresh()->categories()->count())->toBe(1);
});

it('backfill command dry-run reports without inserting rows', function () {
    $userWithoutCategories = User::factory()->create(['locale' => 'en']);

    Artisan::call('categories:seed-defaults', ['--dry-run' => true]);
    $output = Artisan::output();

    expect($output)->toContain('Dry run');
    expect($output)->toContain('1 users would be seeded');

    expect($userWithoutCategories->fresh()->categories()->count())->toBe(0);
});
