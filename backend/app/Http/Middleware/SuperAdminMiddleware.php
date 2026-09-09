<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;

class SuperAdminMiddleware
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
        // Check if admin is authenticated
        if (!auth('admin')->check()) {
            return redirect()->route('admin.auth.login');
        }

        // Check if authenticated admin is super admin
        if (auth('admin')->user()->role_id === 1) {
            return $next($request);
        }

        // Access denied for non-super admins
        Toastr::error(translate('messages.access_denied_super_admin_only'));
        return redirect()->route('admin.dashboard');
    }
}
