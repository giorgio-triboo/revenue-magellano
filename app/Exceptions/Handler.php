<?php

namespace App\Exceptions;

use Exception;
use Throwable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Illuminate\Support\Facades\Log;

class Handler extends ExceptionHandler
{
    /**
     * Un elenco delle eccezioni che non devono essere registrate.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * Un elenco degli input che non devono mai essere visualizzati nei messaggi di errore.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Registra i callback per la gestione delle eccezioni.
     *
     * @return void
     */
    public function register(): void
    {
        // Log::debug('Il metodo register del Handler è stato chiamato.');

        $this->reportable(function (Throwable $e) {
            if ($this->shouldReport($e)) {
                Log::error('Exception occurred:', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        });

        // Gestione errori di autenticazione
        $this->renderable(function (AuthenticationException $e, $request) {
            Log::debug('AuthenticationException catturata nel Handler.');
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Non autenticato.'], 401);
            }
            return redirect()->guest(route('login'))
                ->with('error', 'Devi effettuare l\'accesso per visualizzare questa pagina.');
        });

        // Gestione errori di autorizzazione (AuthorizationException)
        $this->renderable(function (AuthorizationException $e, $request) {
            Log::debug('AuthorizationException catturata nel Handler.');
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Non hai i permessi per eseguire questa azione.'], 403);
            }
            return $this->handleAuthorizationException($request, $e);
        });

        // Gestione degli HttpException con codice 403
        $this->renderable(function (HttpException $e, $request) {
            if ($e->getStatusCode() === 403) {
                Log::debug('HttpException con codice 403 catturata nel Handler.');
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Non autorizzato.'], 403);
                }
                return $this->handleAuthorizationException($request, $e);
            }
        });

        // Gestione degli AccessDeniedHttpException (403)
        $this->renderable(function (AccessDeniedHttpException $e, $request) {
            Log::debug('AccessDeniedHttpException catturata nel Handler.');
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Accesso negato.'], 403);
            }
            return $this->handleAuthorizationException($request, $e);
        });

        // Gestione errori di validazione
        $this->renderable(function (ValidationException $e, $request) {
            Log::debug('ValidationException catturata nel Handler.');
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'I dati forniti non sono validi.',
                    'errors' => $e->errors(),
                ], 422);
            }
            // Lascia che Laravel gestisca la redirezione con errori
        });

        // Gestione modelli non trovati (ModelNotFoundException)
        $this->renderable(function (ModelNotFoundException $e, $request) {
            Log::debug('ModelNotFoundException catturata nel Handler.');
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Risorsa non trovata.'], 404);
            }
            return $this->handleNotFound($request, 'La risorsa richiesta non è stata trovata.');
        });

        // Gestione pagine non trovate (NotFoundHttpException)
        $this->renderable(function (NotFoundHttpException $e, $request) {
            Log::debug('NotFoundHttpException catturata nel Handler.');
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Pagina non trovata.'], 404);
            }
            return $this->handleNotFound($request, 'La pagina richiesta non è stata trovata.');
        });

        // Gestione generica delle eccezioni
        $this->renderable(function (Exception $e, $request) {
            Log::debug('Eccezione generica catturata nel Handler: ' . get_class($e));
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Si è verificato un errore.',
                    'error' => $e->getMessage(),
                ], 500);
            }
            return $this->handleGenericError($request);
        });
    }

    /**
     * Gestisce le eccezioni di autorizzazione.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function handleAuthorizationException($request, Throwable $exception)
    {
        Log::debug('handleAuthorizationException è stato chiamato nel Handler.');

        return redirect()->route('login')
            ->with('error', 'Non hai i permessi per accedere a questa sezione.');
    }

    protected function handleNotFound($request, $message)
    {
        Log::debug('handleNotFound è stato chiamato nel Handler.');

        return redirect()->route('login')
            ->with('error', $message);
    }

    protected function handleGenericError($request)
    {
        Log::debug('handleGenericError è stato chiamato nel Handler.');

        return redirect()->route('login')
            ->with('error', 'Si è verificato un errore. Riprova più tardi.');
    }

    /**
     * Gestisce gli errori 404.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $message
     * @return \Illuminate\Http\RedirectResponse
     */

}
