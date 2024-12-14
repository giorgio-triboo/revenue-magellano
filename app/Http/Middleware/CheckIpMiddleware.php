<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckIpMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Lista di IP autorizzati
        $allowedIps = [
            '127.0.0.1',    // localhost
            'IL_TUO_IP',    // sostituisci con il tuo IP
            // aggiungi altri IP se necessario
        ];

        $clientIp = $request->ip();

        if (!in_array($clientIp, $allowedIps)) {
            abort(403, 'Accesso non autorizzato.');
        }

        return $next($request);
    }
}