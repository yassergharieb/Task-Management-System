<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('login succeeds with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $response
        ->assertOk()
        ->assertJsonStructure([
            'message',
            'token',
            'data' => [
                'id',
                'name',
                'email',
                'created_at',
            ],
        ])
        ->assertJsonPath('message', 'Login successful')
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonMissing(['password', 'remember_token']);

    expect($user->tokens()->count())->toBe(1);
});

test('login fails with invalid password', function () {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response
        ->assertUnauthorized()
        ->assertExactJson([
            'message' => 'Invalid credentials.',
        ]);
});

test('login validation errors are returned', function () {
    $response = $this->postJson('/api/v1/login', []);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password']);
});
