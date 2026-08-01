<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'priority' => fake()->randomElement(TaskPriority::values()),
            'status' => fake()->randomElement(TaskStatus::values()),
            'due_date' => fake()->optional()->date(),
        ];
    }

    public function todo(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TaskStatus::Todo,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TaskStatus::InProgress,
        ]);
    }

    public function done(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TaskStatus::Done,
        ]);
    }
}
