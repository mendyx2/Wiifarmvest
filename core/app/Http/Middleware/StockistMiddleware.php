<?php
// app/Http/Middleware/StockistMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StockistMiddleware
{ 
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
        return redirect()->route('login');
    }

    if (!auth()->user()->isStockist()) {
        return redirect()->route('user.home')->with('error', 'Access denied. Stockist privileges required.');
    }

    return $next($request);


       
    }
}