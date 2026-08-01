<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', 'string', Rule::in(TaskPriority::values())],
            'status' => ['required', 'string', Rule::in(TaskStatus::values())],
            'due_date' => ['nullable', 'date'],
            'attachments' => ['sometimes', 'array'],
            'attachments.*' => ['file', 'mimes:pdf,doc,txt,md', 'max:10240'],
        ];
    }
}
