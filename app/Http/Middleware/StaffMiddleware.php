<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\UserRole;

class StaffMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if (!$user || $user->role->value !== UserRole::StaffAdministrasi->value) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access, Staff Administrasi access required.',
            ], 403);
        }

        return $next($request);
    }
}
