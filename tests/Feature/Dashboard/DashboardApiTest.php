<?php

use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can view dashboard statistics for owned projects and tasks', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $activeProject = Project::factory()->for($user)->create([
        'status' => ProjectStatus::Active,
    ]);
    $completedProject = Project::factory()->for($user)->create([
        'status' => ProjectStatus::Completed,
    ]);
    $otherProject = Project::factory()->for($otherUser)->create([
        'status' => ProjectStatus::Active,
    ]);

    Task::factory()->for($activeProject)->create([
        'status' => TaskStatus::Done,
        'priority' => TaskPriority::High,
    ]);
    Task::factory()->for($activeProject)->create([
        'status' => TaskStatus::Todo,
        'priority' => TaskPriority::Medium,
        'due_date' => now()->subDay()->toDateString(),
    ]);
    Task::factory()->for($completedProject)->create([
        'status' => TaskStatus::InProgress,
        'priority' => TaskPriority::Low,
        'due_date' => now()->addDay()->toDateString(),
    ]);
    Task::factory()->for($otherProject)->create([
        'status' => TaskStatus::Done,
    ]);

    $this
        ->actingAs($user)
        ->getJson('/api/v1/dashboard')
        ->assertOk()
        ->assertJsonPath('message', 'Dashboard statistics retrieved successfully')
        ->assertJsonPath('data.total_projects', 2)
        ->assertJsonPath('data.active_projects', 1)
        ->assertJsonPath('data.total_tasks', 3)
        ->assertJsonPath('data.completed_tasks', 1)
        ->assertJsonPath('data.pending_tasks', 2)
        ->assertJsonPath('data.overdue_tasks', 1);
});

test('dashboard cache is cleared when a task changes', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $this
        ->actingAs($user)
        ->getJson('/api/v1/dashboard')
        ->assertOk()
        ->assertJsonPath('data.total_tasks', 0);

    Task::factory()->for($project)->create([
        'status' => TaskStatus::Todo,
    ]);

    $this
        ->actingAs($user)
        ->getJson('/api/v1/dashboard')
        ->assertOk()
        ->assertJsonPath('data.total_tasks', 1)
        ->assertJsonPath('data.pending_tasks', 1);
});

test('dashboard endpoint requires authentication', function () {
    $this->getJson('/api/v1/dashboard')->assertUnauthorized();
});
