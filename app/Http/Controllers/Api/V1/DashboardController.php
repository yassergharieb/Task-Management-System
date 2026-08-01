<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\DashboardServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardServiceInterface $dashboardService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        return $this->successResponse(
            message: 'Dashboard statistics retrieved successfully',
            data: $this->dashboardService->statistics($request->user()),
        );
    }
}
