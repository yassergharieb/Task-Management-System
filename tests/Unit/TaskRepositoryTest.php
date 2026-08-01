<?php

use App\Contracts\Repositories\TaskRepositoryInterface;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('task repository chunks overdue incomplete tasks pending notification', function () {
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

    $foundIds = [];

    app(TaskRepositoryInterface::class)
        ->chunkOverduePendingNotifications(100, function ($tasks) use (&$foundIds): void {
            foreach ($tasks as $task) {
                $foundIds[] = $task->id;
            }
        });

    expect($foundIds)->toBe([$matchedTask->id]);
});

test('task repository marks overdue notification as sent', function () {
    $task = Task::factory()->create([
        'overdue_notified_at' => null,
    ]);

    $task = app(TaskRepositoryInterface::class)->markOverdueNotified($task);

    expect($task->overdue_notified_at)->not->toBeNull();
});
