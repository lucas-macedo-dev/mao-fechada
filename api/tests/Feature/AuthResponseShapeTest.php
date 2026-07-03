<?php

use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

const ESSENTIAL_USER_FIELDS = [
    'id',
    'name',
    'email',
    'email_verified',
    'locale',
    'profile_photo_url',
    'tutorial_progress',
];

it('generates a unique uuid for every created user', function () {
    $first = User::factory()->create();
    $second = User::factory()->create();

    expect(Str::isUuid($first->uuid))->toBeTrue();
    expect(Str::isUuid($second->uuid))->toBeTrue();
    expect($first->uuid)->not()->toBe($second->uuid);
});

it('exposes only essential fields with the uuid as id on register', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Shape Test',
        'email' => 'shape-register@example.com',
        'password' => 'password1!',
        'locale' => 'en',
    ]);

    $response->assertCreated();

    $user = User::where('email', 'shape-register@example.com')->firstOrFail();

    $response->assertJsonPath('data.user.id', $user->uuid);
    expect(array_keys($response->json('data.user')))->toEqualCanonicalizing(ESSENTIAL_USER_FIELDS);
});

it('exposes only essential fields with the uuid as id on login', function () {
    $user = User::factory()->create(['password' => 'password1!']);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password1!',
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.user.id', $user->uuid);
    expect(array_keys($response->json('data.user')))->toEqualCanonicalizing(ESSENTIAL_USER_FIELDS);
});

it('exposes only essential fields with the uuid as id on me', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/auth/me');

    $response->assertOk();
    $response->assertJsonPath('data.id', $user->uuid);
    expect(array_keys($response->json('data')))->toEqualCanonicalizing(ESSENTIAL_USER_FIELDS);
});

it('exposes only essential fields on tutorial update', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->putJson('/api/v1/users/me/tutorial', [
        'step_id' => 'create-category',
        'completed' => true,
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.id', $user->uuid);
    expect(array_keys($response->json('data')))->toEqualCanonicalizing(ESSENTIAL_USER_FIELDS);
});
