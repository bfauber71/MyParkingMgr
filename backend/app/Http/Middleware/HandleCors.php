<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HandleCors
{
    public function handle(Request $request, Closure $next)
    {
        return $next($request)
            ->header('Access-Control-Allow-Origin', config('app.url'))
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN')
            ->header('Access-Control-Allow-Credentials', 'true');
    }
}
