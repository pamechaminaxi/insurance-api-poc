<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Helpers\ApiResponse;

class CheckUserActive
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // if user inactive
        if ($user && !$user->is_active) {

            // delete current token
            $user->currentAccessToken()?->delete();
            return ApiResponse::error('Your account is inactive. Access denied.', 403);
        }

        return $next($request);
    }
}
