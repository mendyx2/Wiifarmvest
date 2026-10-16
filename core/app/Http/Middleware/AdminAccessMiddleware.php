<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deny-by-default access control for the admin panel.
 *
 * Runs on every authenticated admin route. What each route needs is defined
 * in config/admin_access.php; anything not mapped there is super-admin only,
 * so a new admin page is locked down until someone deliberately opens it.
 */
class AdminAccessMiddleware
{
    private const OPEN = true;

    public function handle(Request $request, Closure $next): Response
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return redirect()->route('admin.login');
        }

        if (!self::allows($admin, (string) $request->route()?->getName(), $request->method())) {
            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }

    /**
     * Whether this admin may use the named route. Shared with the sidebar so
     * a link is only shown when opening it would succeed.
     */
    public static function allows(Admin $admin, string $routeName, string $method = 'GET'): bool
    {
        $config = config('admin_access');

        if ($admin->hasRole($config['super_role'])) {
            return true;
        }

        $required = self::requiredPermission($config, $routeName, strtoupper($method));

        if ($required === self::OPEN) {
            return true;
        }

        if ($required === null) {
            return false;
        }

        try {
            return $admin->hasPermissionTo($required, 'admin');
        } catch (PermissionDoesNotExist) {
            // A permission renamed or deleted in the admin UI must lock the
            // route, not crash it
            return false;
        }
    }

    /**
     * @return string|true|null the permission name, OPEN, or null for super-admin only
     */
    private static function requiredPermission(array $config, string $routeName, string $method): string|bool|null
    {
        if ($routeName === '' || Str::is($config['super_admin_only'], $routeName)) {
            return null;
        }

        if (Str::is($config['open'], $routeName)) {
            return self::OPEN;
        }

        $method = $method === 'HEAD' ? 'GET' : $method;

        foreach ($config['permissions'] as $pattern => $permission) {
            if (Str::is($pattern, $routeName)) {
                return is_array($permission)
                    ? ($permission[$method] ?? $permission['*'] ?? null)
                    : $permission;
            }
        }

        return null;
    }
}
