<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'priority' => ['sometimes', 'string', Rule::in(TaskPriority::values())],
            'status' => ['sometimes', 'string', Rule::in(TaskStatus::values())],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'attachments' => ['sometimes', 'array'],
            'attachments.*' => ['file', 'mimes:pdf,doc,txt,md', 'max:10240'],
        ];
    }
}
