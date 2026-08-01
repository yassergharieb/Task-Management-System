<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AttachmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'path' => $this->resource->path,
            'disk' => $this->resource->disk,
            'mime_type' => $this->resource->mime_type,
            'size' => $this->resource->size,
            'url' => Storage::disk($this->resource->disk)->url($this->resource->path),
            'created_at' => $this->resource->created_at,
        ];
    }
}
