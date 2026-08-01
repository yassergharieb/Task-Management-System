<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Seed project records for the default test user.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        Project::factory()
            ->for($user)
            ->active()
            ->create([
                'name' => 'API Foundation',
                'description' => 'Authentication and project management API setup.',
            ])
            ->attachments()
            ->create([
                'name' => 'api-foundation.pdf',
                'path' => 'projects/seeded/api-foundation.pdf',
                'disk' => 'public',
                'mime_type' => 'application/pdf',
                'size' => 2048,
            ]);

        Project::factory()
            ->for($user)
            ->completed()
            ->create([
                'name' => 'Initial Release',
                'description' => 'First completed project milestone.',
            ]);

        Project::factory()
            ->for($user)
            ->archived()
            ->create([
                'name' => 'Legacy Board',
                'description' => 'Archived planning board from the first iteration.',
            ]);
    }
}
