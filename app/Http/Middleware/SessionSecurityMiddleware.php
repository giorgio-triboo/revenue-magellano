<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionSecurityMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            // Verifica se l'IP o User Agent sono cambiati
            if ($request->session()->has('auth.ip') && 
                $request->session()->has('auth.user_agent')) {
                
                $originalIP = $request->session()->get('auth.ip');
                $originalUA = $request->session()->get('auth.user_agent');
                
                if ($originalIP !== $request->ip() || 
                    $originalUA !== $request->userAgent()) {
                    Auth::logout();
                    $request->session()->invalidate();
                    return redirect()->route('login')
                        ->with('error', 'La sessione è scaduta per motivi di sicurezza.');
                }
            }

            // Verifica timeout sessione
            $lastActive = $request->session()->get('auth.last_active');
            $timeout = config('session.lifetime') * 60; // Converti minuti in secondi
            
            if ($lastActive && (time() - $lastActive) > $timeout) {
                Auth::logout();
                $request->session()->invalidate();
                return redirect()->route('login')
                    ->with('error', 'La sessione è scaduta.');
            }

            // Aggiorna timestamp ultima attività
            $request->session()->put('auth.last_active', time());
        }

        return $next($request);
    }
}