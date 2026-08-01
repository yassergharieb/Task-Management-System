<?php

use App\Jobs\SendOverdueTaskNotificationsJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('schedule dispatches overdue task notification job', function () {
    Queue::fake();

    $this->travelTo(now()->startOfHour());

    $this->artisan('schedule:run')->assertSuccessful();

    Queue::assertPushed(SendOverdueTaskNotificationsJob::class);
});
