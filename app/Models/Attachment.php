<?php

namespace App\Models;

use Database\Factories\AttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'path', 'disk', 'mime_type', 'size'])]
class Attachment extends Model
{
    /** @use HasFactory<AttachmentFactory> */
    use HasFactory;

    /**
     * @return MorphTo<Model, $this>
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function deleteStoredFile(): void
    {
        Storage::disk($this->disk)->delete($this->path);
    }
}
