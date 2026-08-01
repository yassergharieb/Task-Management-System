<?php

namespace App\Jobs;

use App\Contracts\Repositories\TaskRepositoryInterface;
use App\Models\Task;
use App\Notifications\TaskOverdueNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

class SendOverdueTaskNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $taskId,
    ) {}

    public function taskId(): int
    {
        return $this->taskId;
    }

    public function handle(TaskRepositoryInterface $tasks): void
    {
        $task = Task::query()
            ->with('project.user')
            ->find($this->taskId);

        if (! $task instanceof Task || $task->project === null) {
            return;
        }

        Notification::sendNow($task->project->user, new TaskOverdueNotification($task));

        $tasks->markOverdueNotified($task);
    }
}
