<?php

use App\Contracts\Services\ProjectServiceInterface;
use App\Jobs\DeleteProjectAttachmentsJob;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('project service deletes project then dispatches attachment cleanup job', function () {
    Queue::fake();
    Storage::fake('public');

    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $path = UploadedFile::fake()
        ->create('delete-me.pdf', 128, 'application/pdf')
        ->store("projects/{$project->id}", 'public');

    $attachment = $project->attachments()->create([
        'name' => 'delete-me.pdf',
        'path' => $path,
        'disk' => 'public',
        'mime_type' => 'application/pdf',
        'size' => 128,
    ]);

    app(ProjectServiceInterface::class)->delete($user, $project);

    $this->assertDatabaseMissing('projects', [
        'id' => $project->id,
    ]);

    $this->assertDatabaseHas('attachments', [
        'id' => $attachment->id,
    ]);
    Storage::disk('public')->assertExists($path);

    $pushedJob = null;

    Queue::assertPushed(DeleteProjectAttachmentsJob::class, function (DeleteProjectAttachmentsJob $job) use (&$pushedJob): bool {
        $pushedJob = $job;

        return true;
    });

    expect($pushedJob)->toBeInstanceOf(DeleteProjectAttachmentsJob::class);

    $pushedJob->handle();

    Storage::disk('public')->assertMissing($path);

    $this->assertDatabaseMissing('attachments', [
        'id' => $attachment->id,
    ]);
});
