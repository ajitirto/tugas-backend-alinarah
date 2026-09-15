<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiBuildHeader
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('X-Api-Build', 'tulip-58');

        return $response;
    }
}
