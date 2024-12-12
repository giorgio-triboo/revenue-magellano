<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureTermsAccepted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verifica se l'utente è autenticato
        if (!$request->user()) {
            return redirect()->route('login');
        }

        // Verifica se l'utente ha un account verificato e attivo
        if (!$request->user()->isActiveAndVerified()) {
            Log::info('Utente non verificato o non attivo', [
                'user_id' => $request->user()->id,
                'is_active' => $request->user()->is_active,
                'email_verified_at' => $request->user()->email_verified_at
            ]);
            
            auth()->logout();
            return redirect()->route('login')
                ->with('warning', 'Il tuo account deve essere verificato e attivato prima di poter accedere.');
        }

        // Se l'utente non ha accettato i termini e non sta già visualizzando la pagina dei termini
        if (!$request->user()->hasAcceptedTerms() && !$request->routeIs('terms.*')) {
            Log::info('Redirecting user to terms acceptance page', [
                'user_id' => $request->user()->id,
                'email' => $request->user()->email,
                'current_route' => $request->route()->getName()
            ]);

            // Memorizza l'URL che l'utente stava tentando di accedere
            if (!$request->routeIs('logout')) {
                session(['url.intended' => $request->url()]);
            }

            return redirect()->route('terms.show')
                ->with('warning', 'È necessario accettare i termini e le condizioni per continuare.');
        }

        return $next($request);
    }
}