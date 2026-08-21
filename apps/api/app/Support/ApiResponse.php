<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

/**
 * Builds the response envelope required by PRD §13:
 * { success, data, error } with a machine-readable error.code
 * and a localized error.message.
 */
class ApiResponse
{
    public static function success(mixed $data = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'error' => null,
        ], $status);
    }

    public static function error(string $code, string $message, int $status, ?array $fieldErrors = null): JsonResponse
    {
        $error = [
            'code' => $code,
            'message' => $message,
        ];

        if ($fieldErrors !== null) {
            $error['field_errors'] = $fieldErrors;
        }

        return response()->json([
            'success' => false,
            'data' => null,
            'error' => $error,
        ], $status);
    }
}
