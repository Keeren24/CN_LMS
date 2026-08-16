<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureIsStudent
{
    public function handle(Request $request, Closure $next)
    {
        if (! auth()->check() || ! auth()->user()->student_id) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
