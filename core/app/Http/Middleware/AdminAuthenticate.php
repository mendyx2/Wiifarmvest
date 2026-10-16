<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        \Log::info('AdminAuthenticate middleware called');
        \Log::info('Auth guards check:', [
            'admin guard check' => Auth::guard('admin')->check(),
            'default guard check' => Auth::check(),
            'user' => Auth::guard('admin')->user(),
        ]);

        if (!Auth::guard('admin')->check()) {
            \Log::warning('Admin not authenticated, redirecting to login');
            return redirect()->route('admin.login')->with('error', 'Please login as admin first.');
        }

        \Log::info('Admin authenticated successfully: ' . Auth::guard('admin')->user()->email);
        return $next($request);
    }
}