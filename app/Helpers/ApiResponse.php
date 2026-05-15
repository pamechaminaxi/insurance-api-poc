<?php

namespace App\Helpers;

class ApiResponse
{
    public static function success($message, $data = [], $code = 200)
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        // Handle pagination meta if data is a paginator
        if ($data instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $response['data'] = $data->items();
            $response['meta'] = [
                'current_page' => $data->currentPage(),
                'total' => $data->total(),
                'per_page' => $data->perPage(),
                'last_page' => $data->lastPage(),
            ];
        }

        return response()->json($response, $code);
    }

    public static function error($message, $code = 400, $errors = [])
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors ?: null
        ], $code);
    }
}
