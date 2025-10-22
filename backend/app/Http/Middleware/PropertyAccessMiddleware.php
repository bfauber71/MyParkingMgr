<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PropertyAccessMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if ($user->isAdmin() || $user->isOperator()) {
            return $next($request);
        }

        return $next($request);
    }
}
