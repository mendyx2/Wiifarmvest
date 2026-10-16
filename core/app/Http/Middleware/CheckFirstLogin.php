<?php
// app/Http/Middleware/CheckFirstLogin.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckFirstLogin
{ 
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && !session('has_logged_in_before')) {
            // Create a welcome flashcard for first login
            Auth::user()->flashcardPreferences()->create([
                'type' => 'video',
                'content_url' => '/videos/welcome.mp4',
                'title' => 'Welcome to Our Platform!',
                'description' => 'Get started with this quick tour',
                'is_active' => true,
            ]);
            
            session(['has_logged_in_before' => true]);
        }
        
        return $next($request); 
    }
}