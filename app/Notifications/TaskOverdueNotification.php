<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskOverdueNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Task $task,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $dueDate = $this->task->due_date ?? 'N/A';

        return (new MailMessage)
            ->subject('Task overdue: '.$this->task->title)
            ->line("Task '{$this->task->title}' is overdue.")
            ->line('Due date: '.$dueDate);
    }
}
