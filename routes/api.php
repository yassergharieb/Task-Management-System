<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\TaskController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->as('v1.')
    ->group(function (): void {
        Route::post('/register', [AuthController::class, 'register'])->name('register');
        Route::post('/login', [AuthController::class, 'login'])->name('login');
        Route::post('/logout', [AuthController::class, 'logout'])
            ->middleware('auth:sanctum')
            ->name('logout');

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/dashboard', DashboardController::class)->name('dashboard');
            Route::apiResource('projects', ProjectController::class);
            Route::apiResource('projects.tasks', TaskController::class)->shallow();
        });

    });
