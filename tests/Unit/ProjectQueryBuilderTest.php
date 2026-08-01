<?php

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use App\QueryBuilders\ProjectQueryBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('project query builder scopes projects to the user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $ownedProject = Project::factory()->for($user)->create();
    Project::factory()->for($otherUser)->create();

    $projects = ProjectQueryBuilder::forUser($user)
        ->toBase()
        ->get();

    expect($projects)
        ->toHaveCount(1)
        ->first()->id->toBe($ownedProject->id);
});

test('project query builder filters by status and search term', function () {
    $user = User::factory()->create();

    $matchedProject = Project::factory()->for($user)->create([
        'name' => 'Mobile App Launch',
        'description' => 'Build the app release board.',
        'status' => ProjectStatus::Active,
    ]);

    Project::factory()->for($user)->create([
        'name' => 'Mobile App Archive',
        'status' => ProjectStatus::Archived,
    ]);

    Project::factory()->for($user)->create([
        'name' => 'Website Refresh',
        'status' => ProjectStatus::Active,
    ]);

    $projects = ProjectQueryBuilder::forUser($user)
        ->status(ProjectStatus::Active->value)
        ->search('mobile')
        ->toBase()
        ->get();

    expect($projects)
        ->toHaveCount(1)
        ->first()->id->toBe($matchedProject->id);
});
