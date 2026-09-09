<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Exception;

class APIGuestMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if($request->header('Authorization') && app('auth')->guard('api')){
            try {
                $user = auth('api')->user();
                if ($user) {
                    $request->merge(['user' => $user]);
                    return $next($request);
                }
            } catch (Exception $e) {
                // Invalid or malformed token - continue to check guest_id
            }
        }

        if($request->guest_id){
            return $next($request);
        }

        return response()->json(['errors' => 'Unauthorized'], 401);
    }
}
