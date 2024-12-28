<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Enums\UserRole;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()->role !== UserRole::Admin) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized, Admin access required.'
            ], 403);
        }
        return $next($request);
    }
}