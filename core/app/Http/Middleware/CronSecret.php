<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CronSecret
{
    public function handle(Request $request, Closure $next)
    {
        $secret = config('app.cron_secret');

        if (!$secret || $request->get('token') !== $secret) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
