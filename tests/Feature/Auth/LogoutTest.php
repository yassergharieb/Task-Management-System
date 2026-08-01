<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this
        ->withToken($token)
        ->postJson('/api/v1/logout');

    $response
        ->assertOk()
        ->assertExactJson([
            'message' => 'Logout successful',
        ]);

    expect($user->tokens()->count())->toBe(0);
});

test('guest cannot logout', function () {
    $response = $this->postJson('/api/v1/logout');

    $response->assertUnauthorized();
});
