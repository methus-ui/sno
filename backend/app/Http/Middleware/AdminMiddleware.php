<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class AdminMiddleware
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
        if(Auth::guard('admin')->user() && Auth::guard('admin')->user()->is_logged_in == 0){
            auth()->guard('admin')->logout();
        }

        // Security Fix: Check if admin employee application is approved
        if (Auth::guard('admin')->check()) {
            $admin = Auth::guard('admin')->user();

            // Check if this is an admin employee (not super admin)
            if ($admin->role_id !== 1) {
                // Check if application is NOT approved (status must be 1)
                if (is_null($admin->status) || $admin->status !== 1) {
                    auth()->guard('admin')->logout();

                    if (is_null($admin->status)) {
                        \Brian2694\Toastr\Facades\Toastr::error(translate('messages.your_application_is_pending_approval'));
                    } else {
                        \Brian2694\Toastr\Facades\Toastr::error(translate('messages.your_application_has_been_denied'));
                    }

                    return redirect()->route('home');
                }
            }

            return $next($request);
        }
        return redirect()->route('home');
        // return redirect()->route('login');
        // return redirect()->route('admin.auth.login');
    }
}
