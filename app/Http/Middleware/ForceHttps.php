<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceHttps
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->secure() || app()->environment(['local', 'testing'])) {
            return $next($request);
        }

        return redirect()->secure($request->getRequestUri(), 301);
    }
}