<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpFoundation\Response;

trait JsonResponseTrait
{
    /**
     * @param  JsonResource|array<string, mixed>|null  $data
     */
    public function successResponse(string $message,
        JsonResource|array|null $data = null,
        int $status = Response::HTTP_OK,
        ?string $token = null): JsonResponse
    {
        $response = [
            'message' => $message,
        ];

        if ($token !== null) {
            $response['token'] = $token;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $status);
    }

    public function errorResponse(string $message, int $status): JsonResponse
    {
        return response()->json([
            'message' => $message,
        ], $status);
    }
}
