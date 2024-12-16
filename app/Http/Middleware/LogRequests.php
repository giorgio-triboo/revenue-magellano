<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogRequests
{
    public function handle(Request $request, Closure $next)
    {
        Log::channel('upload')->emergency('LogRequests: Richiesta ricevuta', [
            'uri' => $request->getRequestUri(),
            'method' => $request->method(),
            'user_authenticated' => auth()->check(),
            'user_id' => auth()->id(),
            'session_has_token' => $request->session()->has('_token'),
            'csrf_token_header' => $request->header('X-CSRF-TOKEN'),
            'csrf_present' => $request->header('X-CSRF-TOKEN') !== null,
        ]);

        $response = $next($request);

        Log::channel('upload')->emergency('LogRequests: Risposta generata', [
            'status' => $response->status(),
            'content_type' => $response->headers->get('Content-Type'),
        ]);

        return $response;
    }
}