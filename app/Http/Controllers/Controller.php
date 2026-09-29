<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

abstract class Controller
{
    /**
     * Standard success JSON response.
     */
    protected function successResponse(mixed $data = null, string $message = 'Operasi berhasil', int $code = 200, array $extra = []): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        if (! empty($extra)) {
            $response = array_merge($response, $extra);
        }

        return response()->json($response, $code);
    }

    /**
     * Standard error / validation JSON response.
     */
    protected function errorResponse(string $message = 'Terjadi kesalahan', int $code = 400, mixed $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }
}
