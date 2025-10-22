<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReadOnlyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if ($user->isOperator() && in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Operators have read-only access'
            ], 403);
        }

        return $next($request);
    }
}
