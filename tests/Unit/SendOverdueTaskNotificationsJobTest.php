<?php

use App\Enums\TaskStatus;
use App\Jobs\SendOverdueTaskNotificationJob;
use App\Jobs\SendOverdueTaskNotificationsJob;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskOverdueNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('job dispatches notification jobs for overdue incomplete tasks only', function () {
    Queue::fake();

    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $overdueTask = Task::factory()->for($project)->create([
        'title' => 'Overdue task',
        'status' => TaskStatus::Todo,
        'due_date' => now()->subDay()->toDateString(),
        'overdue_notified_at' => null,
    ]);

    Task::factory()->for($project)->create([
        'status' => TaskStatus::Done,
        'due_date' => now()->subDay()->toDateString(),
        'overdue_notified_at' => null,
    ]);

    Task::factory()->for($project)->create([
        'status' => TaskStatus::Todo,
        'due_date' => now()->addDay()->toDateString(),
        'overdue_notified_at' => null,
    ]);

    Task::factory()->for($project)->create([
        'status' => TaskStatus::InProgress,
        'due_date' => now()->subDay()->toDateString(),
        'overdue_notified_at' => now()->subHour(),
    ]);

    app()->call([(new SendOverdueTaskNotificationsJob), 'handle']);

    Queue::assertPushed(
        SendOverdueTaskNotificationJob::class,
        fn (SendOverdueTaskNotificationJob $job): bool => $job->taskId() === $overdueTask->id,
    );

    Queue::assertPushed(SendOverdueTaskNotificationJob::class, 1);

    expect($overdueTask->refresh()->overdue_notified_at)->toBeNull();
});

test('job does not resend notifications after a task was marked notified', function () {
    Queue::fake();

    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    Task::factory()->for($project)->create([
        'status' => TaskStatus::Todo,
        'due_date' => now()->subDay()->toDateString(),
        'overdue_notified_at' => now()->subHour(),
    ]);

    app()->call([(new SendOverdueTaskNotificationsJob), 'handle']);

    Queue::assertNotPushed(SendOverdueTaskNotificationJob::class);
});

test('single task notification job marks task notified after notification is sent', function () {
    Notification::fake();

    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $task = Task::factory()->for($project)->create([
        'status' => TaskStatus::Todo,
        'due_date' => now()->subDay()->toDateString(),
        'overdue_notified_at' => null,
    ]);

    app()->call([(new SendOverdueTaskNotificationJob($task->id)), 'handle']);

    Notification::assertSentTo(
        $user,
        TaskOverdueNotification::class,
        fn (TaskOverdueNotification $notification): bool => $notification->task->is($task),
    );

    expect($task->refresh()->overdue_notified_at)->not->toBeNull();
});

test('single task notification job does not mark task notified when notification fails', function () {
    Notification::shouldReceive('sendNow')
        ->once()
        ->andThrow(new RuntimeException('Notification failed'));

    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $task = Task::factory()->for($project)->create([
        'status' => TaskStatus::Todo,
        'due_date' => now()->subDay()->toDateString(),
        'overdue_notified_at' => null,
    ]);

    expect(fn () => app()->call([(new SendOverdueTaskNotificationJob($task->id)), 'handle']))
        ->toThrow(RuntimeException::class);

    expect($task->refresh()->overdue_notified_at)->toBeNull();
});
