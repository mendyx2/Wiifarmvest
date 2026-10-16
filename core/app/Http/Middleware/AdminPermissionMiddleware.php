<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
 
class AdminPermissionMiddleware
{
    public function handle(Request $request, Closure $next, $permission, $guard = 'admin'): Response
    {
        $authGuard = Auth::guard($guard);

        if (!$authGuard->check()) {
            return redirect()->route('admin.login')->with('error', 'Not authenticated as admin.');
        }

        $user = $authGuard->user();

        if (! $user->hasPermissionTo($permission)) {
            abort(403, 'You do not have the required permission.');
        }

        return $next($request);
    }
}