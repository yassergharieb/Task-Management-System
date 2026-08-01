<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Jobs\DeleteProjectAttachmentsJob;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('authenticated user can create a task for an owned project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $response = $this
        ->actingAs($user)
        ->postJson("/api/v1/projects/{$project->id}/tasks", [
            'title' => 'Prepare launch checklist',
            'description' => 'Write rollout tasks.',
            'priority' => TaskPriority::High->value,
            'status' => TaskStatus::Todo->value,
            'due_date' => '2026-08-15',
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('message', 'Task created successfully')
        ->assertJsonPath('data.project_id', $project->id)
        ->assertJsonPath('data.title', 'Prepare launch checklist')
        ->assertJsonPath('data.priority', TaskPriority::High->value)
        ->assertJsonPath('data.status', TaskStatus::Todo->value)
        ->assertJsonPath('data.due_date', '2026-08-15');

    $this->assertDatabaseHas('tasks', [
        'project_id' => $project->id,
        'title' => 'Prepare launch checklist',
        'priority' => TaskPriority::High->value,
        'status' => TaskStatus::Todo->value,
    ]);
});

test('authenticated user can create a task with attachments', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $response = $this
        ->actingAs($user)
        ->postJson("/api/v1/projects/{$project->id}/tasks", [
            'title' => 'Attach task brief',
            'priority' => TaskPriority::Medium->value,
            'status' => TaskStatus::Todo->value,
            'attachments' => [
                UploadedFile::fake()->create('task-brief.pdf', 128, 'application/pdf'),
            ],
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.attachments.0.name', 'task-brief.pdf')
        ->assertJsonPath('data.attachments.0.disk', 'public');

    $path = $response->json('data.attachments.0.path');

    Storage::disk('public')->assertExists($path);

    $this->assertDatabaseHas('attachments', [
        'name' => 'task-brief.pdf',
        'disk' => 'public',
        'attachable_type' => 'task',
    ]);
});

test('authenticated user can list project tasks with status priority and title search filters', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $otherProject = Project::factory()->for($user)->create();

    $matchedTask = Task::factory()->for($project)->create([
        'title' => 'Mobile release QA',
        'status' => TaskStatus::InProgress,
        'priority' => TaskPriority::High,
    ]);

    Task::factory()->for($project)->create([
        'title' => 'Mobile release docs',
        'status' => TaskStatus::Todo,
        'priority' => TaskPriority::High,
    ]);

    Task::factory()->for($project)->create([
        'title' => 'Mobile release budget',
        'status' => TaskStatus::InProgress,
        'priority' => TaskPriority::Low,
    ]);

    Task::factory()->for($otherProject)->create([
        'title' => 'Mobile release QA',
        'status' => TaskStatus::InProgress,
        'priority' => TaskPriority::High,
    ]);

    $response = $this
        ->actingAs($user)
        ->getJson("/api/v1/projects/{$project->id}/tasks?status=in_progress&priority=high&search=release");

    $response
        ->assertOk()
        ->assertJsonPath('message', 'Tasks retrieved successfully')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $matchedTask->id);
});

test('authenticated user can view update and delete an owned task', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $task = Task::factory()->for($project)->todo()->create([
        'title' => 'Original task',
    ]);

    $this
        ->actingAs($user)
        ->getJson("/api/v1/tasks/{$task->id}")
        ->assertOk()
        ->assertJsonPath('data.title', 'Original task');

    $this
        ->actingAs($user)
        ->patchJson("/api/v1/tasks/{$task->id}", [
            'title' => 'Updated task',
            'status' => TaskStatus::Done->value,
            'priority' => TaskPriority::Low->value,
        ])
        ->assertOk()
        ->assertJsonPath('message', 'Task updated successfully')
        ->assertJsonPath('data.title', 'Updated task')
        ->assertJsonPath('data.status', TaskStatus::Done->value)
        ->assertJsonPath('data.priority', TaskPriority::Low->value);

    $this
        ->actingAs($user)
        ->deleteJson("/api/v1/tasks/{$task->id}")
        ->assertOk()
        ->assertExactJson([
            'message' => 'Task deleted successfully',
        ]);

    $this->assertDatabaseMissing('tasks', [
        'id' => $task->id,
    ]);
});

test('authenticated user can add attachments while updating a task', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $task = Task::factory()->for($project)->create();

    $response = $this
        ->actingAs($user)
        ->patchJson("/api/v1/tasks/{$task->id}", [
            'attachments' => [
                UploadedFile::fake()->create('task-notes.txt', 4, 'text/plain'),
            ],
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.attachments.0.name', 'task-notes.txt');

    Storage::disk('public')->assertExists($response->json('data.attachments.0.path'));
});

test('deleting a task dispatches attachment cleanup after commit', function () {
    Queue::fake();
    Storage::fake('public');

    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $task = Task::factory()->for($project)->create();
    $path = UploadedFile::fake()
        ->create('delete-task-file.pdf', 128, 'application/pdf')
        ->store("tasks/{$task->id}", 'public');

    $task->attachments()->create([
        'name' => 'delete-task-file.pdf',
        'path' => $path,
        'disk' => 'public',
        'mime_type' => 'application/pdf',
        'size' => 128,
    ]);

    $this
        ->actingAs($user)
        ->deleteJson("/api/v1/tasks/{$task->id}")
        ->assertOk();

    Queue::assertPushed(DeleteProjectAttachmentsJob::class);
    Storage::disk('public')->assertExists($path);

    $this->assertDatabaseMissing('tasks', [
        'id' => $task->id,
    ]);
});

test('authenticated user cannot access another users project tasks or task', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->for($otherUser)->create();
    $task = Task::factory()->for($project)->create();

    $this
        ->actingAs($user)
        ->getJson("/api/v1/projects/{$project->id}/tasks")
        ->assertNotFound();

    $this
        ->actingAs($user)
        ->postJson("/api/v1/projects/{$project->id}/tasks", [
            'title' => 'Hacked task',
            'priority' => TaskPriority::High->value,
            'status' => TaskStatus::Todo->value,
        ])
        ->assertNotFound();

    $this
        ->actingAs($user)
        ->getJson("/api/v1/tasks/{$task->id}")
        ->assertNotFound();

    $this
        ->actingAs($user)
        ->patchJson("/api/v1/tasks/{$task->id}", [
            'title' => 'Hacked task',
        ])
        ->assertNotFound();

    $this
        ->actingAs($user)
        ->deleteJson("/api/v1/tasks/{$task->id}")
        ->assertNotFound();
});

test('task endpoints require authentication', function () {
    $project = Project::factory()->create();
    $task = Task::factory()->for($project)->create();

    $this->getJson("/api/v1/projects/{$project->id}/tasks")->assertUnauthorized();
    $this->postJson("/api/v1/projects/{$project->id}/tasks", [])->assertUnauthorized();
    $this->getJson("/api/v1/tasks/{$task->id}")->assertUnauthorized();
    $this->patchJson("/api/v1/tasks/{$task->id}", [])->assertUnauthorized();
    $this->deleteJson("/api/v1/tasks/{$task->id}")->assertUnauthorized();
});
