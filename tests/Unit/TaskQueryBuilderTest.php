<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\QueryBuilders\TaskQueryBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('task query builder scopes tasks to the project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $otherProject = Project::factory()->for($user)->create();

    $ownedTask = Task::factory()->for($project)->create();
    Task::factory()->for($otherProject)->create();

    $tasks = TaskQueryBuilder::forProject($project)
        ->toBase()
        ->get();

    expect($tasks)
        ->toHaveCount(1)
        ->first()->id->toBe($ownedTask->id);
});

test('task query builder filters by status priority and title search', function () {
    $project = Project::factory()->create();

    $matchedTask = Task::factory()->for($project)->create([
        'title' => 'Design task module API',
        'status' => TaskStatus::InProgress,
        'priority' => TaskPriority::High,
    ]);

    Task::factory()->for($project)->create([
        'title' => 'Design task module API',
        'status' => TaskStatus::Todo,
        'priority' => TaskPriority::High,
    ]);

    Task::factory()->for($project)->create([
        'title' => 'Write task docs',
        'status' => TaskStatus::InProgress,
        'priority' => TaskPriority::High,
    ]);

    $tasks = TaskQueryBuilder::forProject($project)
        ->status(TaskStatus::InProgress->value)
        ->priority(TaskPriority::High->value)
        ->search('module')
        ->toBase()
        ->get();

    expect($tasks)
        ->toHaveCount(1)
        ->first()->id->toBe($matchedTask->id);
});

test('task query builder finds overdue incomplete tasks pending notification', function () {
    $project = Project::factory()->create();

    $matchedTask = Task::factory()->for($project)->create([
        'status' => TaskStatus::Todo,
        'due_date' => now()->subDay()->toDateString(),
        'overdue_notified_at' => null,
    ]);

    Task::factory()->for($project)->create([
        'status' => TaskStatus::Done,
        'due_date' => now()->subDay()->toDateString(),
        'overdue_notified_at' => null,
    ]);

    Task::factory()->for($project)->create([
        'status' => TaskStatus::InProgress,
        'due_date' => now()->addDay()->toDateString(),
        'overdue_notified_at' => null,
    ]);

    Task::factory()->for($project)->create([
        'status' => TaskStatus::Todo,
        'due_date' => now()->subDay()->toDateString(),
        'overdue_notified_at' => now()->subHour(),
    ]);

    $tasks = TaskQueryBuilder::overduePendingNotifications()
        ->toBase()
        ->get();

    expect($tasks)
        ->toHaveCount(1)
        ->first()->id->toBe($matchedTask->id);
});
