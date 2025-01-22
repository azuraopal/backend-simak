<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class LogActivityMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (auth()->check() && auth()->user()->role === 'Staff') {
            activity()
                ->causedBy(auth()->user()) 
                ->withProperties([
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'payload' => $request->all(),
                ])
                ->log("Staff accessed {$request->path()}");
        }

        return $response;
    }
}
