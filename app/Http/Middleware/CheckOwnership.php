<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Helpers\ApiResponse;

class CheckOwnership
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, $param = 'user_id')
    {
        $user = auth()->user();

        // Admin can bypass ownership
        if ($user->isAdmin()) {
            return $next($request);
        }

        if ($request->$param != $user->id) {
            return ApiResponse::error('Access denied. Not your resource.', 403);
        }

        return $next($request);
    }
}
