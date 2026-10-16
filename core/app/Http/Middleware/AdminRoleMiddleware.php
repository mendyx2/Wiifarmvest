<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminRoleMiddleware
{
    public function handle(Request $request, Closure $next, $role, $guard = 'admin'): Response
    {
        $authGuard = Auth::guard($guard);

        if (!$authGuard->check()) {
            return redirect()->route('admin.login')->with('error', 'Not authenticated as admin.');
        }

        $user = $authGuard->user();

        if (! $user->hasRole($role)) {
            abort(403, 'You do not have the required role.');
        }

        return $next($request);
    }
}