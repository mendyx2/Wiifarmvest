<?php

namespace App\Http\Middleware;

use Closure;

class Demo
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('DELETE')){
            $notify[] = ['warning', 'You can not change anything over this demo'];
            $notify[] = ['info', 'Contact admin'];
            return back()->withNotify($notify);
        }
        return $next($request);
    }
}
