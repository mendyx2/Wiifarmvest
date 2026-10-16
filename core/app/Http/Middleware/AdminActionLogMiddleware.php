<?php

namespace App\Http\Middleware;

use App\Models\AdminActionLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AdminActionLogMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!Auth::guard('admin')->check()) {
            return $response;
        }

        if ($response->getStatusCode() >= 400) {
            return $response;
        }

        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $response;
        }

        $route = $request->route();
        $routeName = $route ? $route->getName() : null;
        if ($routeName && str_starts_with($routeName, 'admin.system.action.log')) {
            return $response;
        }

        try {
            $admin = Auth::guard('admin')->user();
            $subjectType = null;
            $subjectId = null;
            $routeParams = [];

            if ($route) {
                $routeParams = $this->sanitizeData($route->parameters());

                if (!empty($routeParams)) {
                    $firstKey = array_key_first($routeParams);
                    if (is_string($routeParams[$firstKey]) || is_numeric($routeParams[$firstKey])) {
                        $subjectId = (string) $routeParams[$firstKey];
                    }
                    $subjectType = $firstKey;
                }
            }

            $requestData = $this->sanitizeData($request->except([
                '_token',
                '_method',
                'password',
                'password_confirmation',
                'current_password',
                'new_password',
                'confirm_password',
                'old_password',
                'photo',
                'image',
                'file',
            ]));

            AdminActionLog::create([
                'admin_id' => $admin->id,
                'admin_name' => $admin->name ?? null,
                'admin_username' => $admin->username ?? null,
                'admin_role' => $this->getAdminRoleNames($admin),
                'method' => $request->method(),
                'route_name' => $routeName,
                'uri' => $request->getRequestUri(),
                'action' => $routeName ?? $request->method(),
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'description' => $this->buildDescription($request, $routeName, $routeParams),
                'meta' => [
                    'route_parameters' => $routeParams,
                    'request_data' => $requestData,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);
        } catch (\Throwable $exception) {
            \Log::error('Admin action audit failed: '.$exception->getMessage(), [
                'route' => $routeName,
                'uri' => $request->getRequestUri(),
            ]);
        }

        return $response;
    }

    protected function sanitizeData(array $data): array
    {
        return collect($data)->map(function ($value) {
            if (is_array($value)) {
                return $this->sanitizeData($value);
            }

            if ($value instanceof \Illuminate\Http\UploadedFile) {
                return $value->getClientOriginalName();
            }

            if (is_object($value)) {
                if (method_exists($value, 'getKey')) {
                    return $value->getKey();
                }
                if (method_exists($value, '__toString')) {
                    return (string) $value;
                }
                return null;
            }

            return $value;
        })->toArray();
    }

    protected function getAdminRoleNames($admin): ?string
    {
        if (method_exists($admin, 'getRoleNames')) {
            return $admin->getRoleNames()->join(', ');
        }

        return null;
    }

    protected function buildDescription(Request $request, ?string $routeName, array $routeParameters): string
    {
        $description = ucfirst(strtolower($request->method()));

        if ($routeName) {
            $description .= ' ' . $routeName;
        }

        if (!empty($routeParameters)) {
            $params = [];
            foreach ($routeParameters as $key => $value) {
                $params[] = $key . '=' . (is_scalar($value) ? $value : json_encode($value));
            }
            $description .= ' [' . implode(', ', $params) . ']';
        }

        return $description;
    }
}
