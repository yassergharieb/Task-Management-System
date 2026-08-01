<?php

namespace App\Jobs;

use App\Contracts\Repositories\TaskRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendOverdueTaskNotificationsJob implements ShouldQueue
{
    use Queueable;

    public function handle(TaskRepositoryInterface $tasks): void
    {
        $tasks->chunkOverduePendingNotifications(100, function ($overdueTasks): void {
            foreach ($overdueTasks as $task) {
                SendOverdueTaskNotificationJob::dispatch($task->id);
            }
        });
    }
}
