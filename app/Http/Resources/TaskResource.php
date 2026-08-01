<?php

namespace App\Http\Resources;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'project_id' => $this->resource->project_id,
            'title' => $this->resource->title,
            'description' => $this->resource->description,
            'priority' => $this->resource->priority instanceof TaskPriority
                ? $this->resource->priority->value
                : $this->resource->priority,
            'status' => $this->resource->status instanceof TaskStatus
                ? $this->resource->status->value
                : $this->resource->status,
            'due_date' => $this->resource->due_date?->format('Y-m-d'),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
