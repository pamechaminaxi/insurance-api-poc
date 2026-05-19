<?php

namespace App\Helpers;

class ApiResponse
{
     /**
     * Generate a standardized success API response.
     *
     * This helper ensures all successful API responses follow the same structure.
     * It also automatically attaches pagination metadata when a paginator instance is passed.
     *
     * Example response structure:
     * {
     *   "success": true,
     *   "message": "Users fetched successfully",
     *   "data": [...],
     *   "meta": {
     *       "current_page": 1,
     *       "last_page": 5,
     *       "per_page": 10,
     *       "total": 50
     *   }
     * }
     *
     * @param string $message  Human-readable success message
     * @param mixed  $data     Response payload (array | object | paginator)
     * @param int    $code     HTTP status code (default: 200)
     * @return \Illuminate\Http\JsonResponse
     */
    public static function success($message, $data = [], $code = 200)
    {
        // Base response structure for successful API calls
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        // If the returned data is paginated, attach pagination meta info
        if ($data instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $response['data'] = $data->items(); // Only return actual records

            // Pagination metadata
            $response['meta'] = [
                'current_page' => $data->currentPage(),
                'total' => $data->total(),
                'per_page' => $data->perPage(),
                'last_page' => $data->lastPage(),
            ];
        }

        // Return JSON response with HTTP status code
        return response()->json($response, $code);
    }

    /**
     * Generate a standardized error API response.
     *
     * Example response structure:
     * {
     *   "success": false,
     *   "message": "Validation failed",
     *   "errors": {...}
     * }
     *
     * @param string $message  Error message
     * @param mixed  $errors   Error details (validation errors / exception message)
     * @param int    $code     HTTP status code (default: 400)
     * @return \Illuminate\Http\JsonResponse
     */
    public static function error($message, $code = 400, $errors = [])
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors ?: null
        ], $code);
    }
}
