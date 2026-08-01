<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\ProjectServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListProjectsRequest;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProjectController extends Controller
{
    public function __construct(
        private readonly ProjectServiceInterface $projectService,
    ) {}

    public function index(ListProjectsRequest $request): JsonResponse
    {
        $projects = $this->projectService->list($request->user(), $request->validated());

        return $this->successResponse(
            message: 'Projects retrieved successfully',
            data: ProjectResource::collection($projects),
        );
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = $this->projectService->create($request->user(), $request->validated());

        return $this->successResponse(
            message: 'Project created successfully',
            data: new ProjectResource($project),
            status: Response::HTTP_CREATED,
        );
    }

    public function show(Request $request, Project $project): JsonResponse
    {
        $project = $this->projectService->view($request->user(), $project);

        return $this->successResponse(
            message: 'Project retrieved successfully',
            data: new ProjectResource($project),
        );
    }

    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $project = $this->projectService->update(
            $request->user(),
            $project,
            $request->validated(),
        );

        return $this->successResponse(
            message: 'Project updated successfully',
            data: new ProjectResource($project),
        );
    }

    public function destroy(Request $request, Project $project): JsonResponse
    {
        $this->projectService->delete($request->user(), $project);

        return $this->successResponse(
            message: 'Project deleted successfully',
        );
    }
}
