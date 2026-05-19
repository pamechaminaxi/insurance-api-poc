<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    //list all activity logs [GET] using service and paginate logs for admin only
    public function index(Request $request)
    {
        //validate request using form requests
        $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);
        
        try {
            $logs = ActivityLog::with('user')
                ->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 15);

            return ApiResponse::success('Activity logs fetched', $logs);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }
}
