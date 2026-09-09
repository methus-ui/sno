<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BargainingEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!config('bargaining.enabled', true)) {
            return response()->json([
                'errors' => [
                    ['code' => 'bargaining_disabled', 'message' => 'Bargaining mode is currently unavailable']
                ]
            ], 503);
        }

        return $next($request);
    }
}
