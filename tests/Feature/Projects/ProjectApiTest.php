<?php

use App\Enums\ProjectStatus;
use App\Jobs\DeleteProjectAttachmentsJob;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('authenticated user can create a project', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->postJson('/api/v1/projects', [
            'name' => 'New Project',
            'description' => 'Project description',
            'status' => ProjectStatus::Active->value,
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('message', 'Project created successfully')
        ->assertJsonPath('data.name', 'New Project')
        ->assertJsonPath('data.status', ProjectStatus::Active->value);

    $this->assertDatabaseHas('projects', [
        'user_id' => $user->id,
        'name' => 'New Project',
        'status' => ProjectStatus::Active->value,
    ]);
});

test('authenticated user can create a project with attachments', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->postJson('/api/v1/projects', [
            'name' => 'Project With Attachments',
            'description' => 'Project description',
            'status' => ProjectStatus::Active->value,
            'attachments' => [
                UploadedFile::fake()->create('brief.pdf', 128, 'application/pdf'),
            ],
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.attachments.0.name', 'brief.pdf')
        ->assertJsonPath('data.attachments.0.disk', 'public');

    $path = $response->json('data.attachments.0.path');

    Storage::disk('public')->assertExists($path);

    $this->assertDatabaseHas('attachments', [
        'name' => 'brief.pdf',
        'disk' => 'public',
        'attachable_type' => 'project',
    ]);
});

test('authenticated user can list only owned projects', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Project::factory()->for($user)->create([
        'name' => 'Owned Project',
        'status' => ProjectStatus::Completed,
    ]);
    Project::factory()->for($otherUser)->create([
        'name' => 'Other Project',
        'status' => ProjectStatus::Completed,
    ]);

    $response = $this
        ->actingAs($user)
        ->getJson('/api/v1/projects?status=completed');

    $response
        ->assertOk()
        ->assertJsonPath('message', 'Projects retrieved successfully')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Owned Project');
});

test('authenticated user can view update and delete an owned project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->active()->create([
        'name' => 'Original Project',
    ]);
    $task = Task::factory()->for($project)->create();

    $this
        ->actingAs($user)
        ->getJson("/api/v1/projects/{$project->id}")
        ->assertOk()
        ->assertJsonPath('data.name', 'Original Project');

    $this
        ->actingAs($user)
        ->patchJson("/api/v1/projects/{$project->id}", [
            'name' => 'Updated Project',
            'status' => ProjectStatus::Completed->value,
        ])
        ->assertOk()
        ->assertJsonPath('message', 'Project updated successfully')
        ->assertJsonPath('data.name', 'Updated Project')
        ->assertJsonPath('data.status', ProjectStatus::Completed->value);

    $this
        ->actingAs($user)
        ->deleteJson("/api/v1/projects/{$project->id}")
        ->assertOk()
        ->assertExactJson([
            'message' => 'Project deleted successfully',
        ]);

    $this->assertSoftDeleted('projects', [
        'id' => $project->id,
    ]);
    $this->assertSoftDeleted('tasks', [
        'id' => $task->id,
    ]);
});

test('authenticated user can add attachments while updating a project', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $project = Project::factory()->for($user)->active()->create();

    $response = $this
        ->actingAs($user)
        ->patchJson("/api/v1/projects/{$project->id}", [
            'attachments' => [
                UploadedFile::fake()->create('notes.txt', 4, 'text/plain'),
            ],
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.attachments.0.name', 'notes.txt');

    Storage::disk('public')->assertExists($response->json('data.attachments.0.path'));
});

test('project attachments must use allowed file extensions', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->postJson('/api/v1/projects', [
            'name' => 'Invalid Attachment Project',
            'description' => 'Project description',
            'status' => ProjectStatus::Active->value,
            'attachments' => [
                UploadedFile::fake()->image('avatar.jpg'),
            ],
        ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['attachments.0']);
});

test('project update attachments must use allowed file extensions', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->active()->create();

    $response = $this
        ->actingAs($user)
        ->patchJson("/api/v1/projects/{$project->id}", [
            'attachments' => [
                UploadedFile::fake()->image('avatar.jpg'),
            ],
        ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['attachments.0']);
});

test('deleting a project dispatches attachment cleanup after commit', function () {
    Queue::fake();
    Storage::fake('public');

    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $path = UploadedFile::fake()
        ->create('delete-me.pdf', 128, 'application/pdf')
        ->store("projects/{$project->id}", 'public');

    $project->attachments()->create([
        'name' => 'delete-me.pdf',
        'path' => $path,
        'disk' => 'public',
        'mime_type' => 'application/pdf',
        'size' => 128,
    ]);

    $this
        ->actingAs($user)
        ->deleteJson("/api/v1/projects/{$project->id}")
        ->assertOk();

    Queue::assertPushed(DeleteProjectAttachmentsJob::class);
    Storage::disk('public')->assertExists($path);

    $this->assertSoftDeleted('projects', [
        'id' => $project->id,
    ]);

    $this->assertDatabaseHas('attachments', [
        'path' => $path,
    ]);
});

test('authenticated user cannot access another users project', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->for($otherUser)->create();

    $this
        ->actingAs($user)
        ->getJson("/api/v1/projects/{$project->id}")
        ->assertNotFound();

    $this
        ->actingAs($user)
        ->patchJson("/api/v1/projects/{$project->id}", [
            'name' => 'Hacked Project',
        ])
        ->assertNotFound();

    $this
        ->actingAs($user)
        ->deleteJson("/api/v1/projects/{$project->id}")
        ->assertNotFound();
});

test('project endpoints require authentication', function () {
    $project = Project::factory()->create();

    $this->getJson('/api/v1/projects')->assertUnauthorized();
    $this->postJson('/api/v1/projects', [])->assertUnauthorized();
    $this->getJson("/api/v1/projects/{$project->id}")->assertUnauthorized();
    $this->patchJson("/api/v1/projects/{$project->id}", [])->assertUnauthorized();
    $this->deleteJson("/api/v1/projects/{$project->id}")->assertUnauthorized();
});
