<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Project::class)->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('priority')->default(TaskPriority::Medium->value);
            $table->string('status')->default(TaskStatus::Todo->value);
            $table->date('due_date')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
