<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attachable_type' => 'project',
            'attachable_id' => Project::factory(),
            'name' => fake()->word().'.pdf',
            'path' => 'projects/'.fake()->uuid().'.pdf',
            'disk' => 'public',
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(1024, 1024 * 1024),
        ];
    }
}
