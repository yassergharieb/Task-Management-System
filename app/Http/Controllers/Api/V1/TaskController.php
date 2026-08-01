<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\TaskServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListTasksRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TaskController extends Controller
{
    public function __construct(
        private readonly TaskServiceInterface $taskService,
    ) {}

    public function index(ListTasksRequest $request, Project $project): JsonResponse
    {
        $tasks = $this->taskService->list($request->user(), $project, $request->validated());

        return $this->successResponse(
            message: 'Tasks retrieved successfully',
            data: TaskResource::collection($tasks),
        );
    }

    public function store(StoreTaskRequest $request, Project $project): JsonResponse
    {
        $task = $this->taskService->create($request->user(), $project, $request->validated());

        return $this->successResponse(
            message: 'Task created successfully',
            data: new TaskResource($task),
            status: Response::HTTP_CREATED,
        );
    }

    public function show(Request $request, Task $task): JsonResponse
    {
        $task = $this->taskService->view($request->user(), $task);

        return $this->successResponse(
            message: 'Task retrieved successfully',
            data: new TaskResource($task),
        );
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $task = $this->taskService->update(
            $request->user(),
            $task,
            $request->validated(),
        );

        return $this->successResponse(
            message: 'Task updated successfully',
            data: new TaskResource($task),
        );
    }

    public function destroy(Request $request, Task $task): JsonResponse
    {
        $this->taskService->delete($request->user(), $task);

        return $this->successResponse(
            message: 'Task deleted successfully',
        );
    }
}
