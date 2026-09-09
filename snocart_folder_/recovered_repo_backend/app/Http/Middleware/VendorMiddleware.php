<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VendorMiddleware
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
        if (Auth::guard('vendor')->check()) {
            if(!auth('vendor')->user()->status)
            {
                auth()->guard('vendor')->logout();
                return redirect()->route('home');
                // return redirect()->route('vendor.auth.login');
            }
            return $next($request);
        }
        else if (Auth::guard('vendor_employee')->check()) {
            $employee = Auth::guard('vendor_employee')->user();

            if($employee->is_logged_in == 0)
            {
                auth()->guard('vendor_employee')->logout();
                return redirect()->route('home');
                // return redirect()->route('vendor.auth.login');
            }

            // Security Fix: Check if vendor employee application is approved
            if (is_null($employee->application_status) || $employee->application_status !== 1) {
                auth()->guard('vendor_employee')->logout();

                if (is_null($employee->application_status)) {
                    \Brian2694\Toastr\Facades\Toastr::error(translate('messages.your_application_is_pending_approval'));
                } else {
                    \Brian2694\Toastr\Facades\Toastr::error(translate('messages.your_application_has_been_denied'));
                }

                return redirect()->route('home');
            }

            if(!$employee->store->status)
            {
                auth()->guard('vendor_employee')->logout();
                return redirect()->route('home');
                // return redirect()->route('vendor.auth.login');
            }
            return $next($request);
        }
        return redirect()->route('home');
        // return redirect()->route('vendor.auth.login');
    }
}
