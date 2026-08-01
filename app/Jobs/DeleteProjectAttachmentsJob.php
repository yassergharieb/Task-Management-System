<?php

namespace App\Jobs;

use App\Models\Attachment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeleteProjectAttachmentsJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int, int>  $attachmentIds
     */
    public function __construct(
        private readonly array $attachmentIds,
    ) {}

    public function handle(): void
    {
        Attachment::query()
            ->whereKey($this->attachmentIds)
            ->get()
            ->each(function (Attachment $attachment): void {
                $attachment->deleteStoredFile();
                $attachment->delete();
            });
    }
}
