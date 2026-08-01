<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can register successfully', function () {
    $response = $this->postJson('/api/v1/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response
        ->assertCreated()
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
        ->assertJsonPath('message', 'Registration successful')
        ->assertJsonPath('data.name', 'Test User')
        ->assertJsonPath('data.email', 'test@example.com')
        ->assertJsonMissing(['password', 'remember_token']);

    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
    ]);

    expect(User::first()?->tokens()->count())->toBe(1);
});

test('duplicate email is rejected', function () {
    User::factory()->create([
        'email' => 'test@example.com',
    ]);

    $response = $this->postJson('/api/v1/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('registration validation errors are returned', function () {
    $response = $this->postJson('/api/v1/register', []);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});
