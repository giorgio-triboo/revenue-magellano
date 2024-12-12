<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $roles
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $roles)
    {
        $requestId = uniqid('req_');

        // Log iniziale della richiesta
        Log::debug('CheckRole: Inizio verifica ruoli', [
            'request_id' => $requestId,
            'user_id' => auth()->id(),
            'requested_roles' => $roles,
            'user_role' => auth()->user()?->role?->code ?? 'no_role',
            'path' => $request->path(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        // Verifica autenticazione
        if (!$request->user()) {
            Log::warning('CheckRole: Utente non autenticato', [
                'request_id' => $requestId,
                'ip' => $request->ip(),
                'path' => $request->path()
            ]);

            return redirect()->route('login')
                ->with('error', 'Devi effettuare l\'accesso per visualizzare questa sezione.');
        }

        // Verifica presenza ruolo
        if (!$request->user()->role) {
            Log::warning('CheckRole: Utente senza ruolo', [
                'request_id' => $requestId,
                'user_id' => auth()->id(),
                'path' => $request->path()
            ]);

            return redirect()->route('login')
                ->with('error', 'Utente senza ruolo assegnato. Contatta l\'amministratore.');
        }

        // Array dei ruoli permessi
        $allowedRoles = array_map('trim', explode(',', $roles));

        Log::debug('CheckRole: Verifica permessi', [
            'request_id' => $requestId,
            'user_id' => auth()->id(),
            'user_role' => $request->user()->role->code,
            'allowed_roles' => $allowedRoles,
            'has_permission' => in_array($request->user()->role->code, $allowedRoles)
        ]);

        // Verifica ruolo
        if (!in_array($request->user()->role->code, $allowedRoles)) {
            Log::warning('CheckRole: Accesso negato - Ruolo non autorizzato', [
                'request_id' => $requestId,
                'user_id' => $request->user()->id,
                'user_role' => $request->user()->role->code,
                'required_roles' => $allowedRoles,
                'path' => $request->path()
            ]);

            // Se è una richiesta AJAX
            if ($request->ajax()) {
                return response()->json([
                    'error' => 'Non hai i permessi necessari per eseguire questa azione.'
                ], 403);
            }

            return redirect()->back()
                ->with('error', 'Non hai i permessi necessari per accedere a questa sezione.');
        }

        // Verifica speciale per i consuntivi
        if (
            $request->route()->getName() === 'statements.index' ||
            $request->route()->getName() === 'statements.show'
        ) {

            // Se è un publisher, verifica che stia accedendo solo ai suoi consuntivi pubblicati
            if ($request->user()->role->code === 'publisher') {
                $statement = $request->route('statement');

                if (
                    $statement && (!$statement->is_published ||
                        $statement->publisher_id !== $request->user()->publisher_id)
                ) {

                    Log::warning('CheckRole: Accesso negato - Consuntivo non autorizzato', [
                        'request_id' => $requestId,
                        'user_id' => $request->user()->id,
                        'statement_id' => $statement->id,
                        'statement_status' => $statement->is_published,
                        'statement_publisher_id' => $statement->publisher_id,
                        'user_publisher_id' => $request->user()->publisher_id
                    ]);

                    return redirect()->back()
                        ->with('error', 'Non hai i permessi per visualizzare questo consuntivo.');
                }
            }
        }

        Log::info('CheckRole: Accesso autorizzato', [
            'request_id' => $requestId,
            'user_id' => auth()->id(),
            'user_role' => $request->user()->role->code,
            'path' => $request->path()
        ]);

        return $next($request);
    }
}