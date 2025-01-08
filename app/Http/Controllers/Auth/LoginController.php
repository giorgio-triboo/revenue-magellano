<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Store session security identifiers
        $request->session()->put('auth.ip', $request->ip());
        $request->session()->put('auth.user_agent', $request->userAgent());
        $request->session()->put('auth.last_active', time());

        // Credenziali di test per l'utente admin
        if ($request->email === 'admin@admin.it' && $request->password === 'admin') {
            $user = User::findOrFail(1);
            Log::info('Accesso effettuato con credenziali di test', [
                'user_id' => 1,
                'role' => 'admin',
            ]);
            Auth::login($user, $request->filled('remember'));
            $request->session()->regenerate();
            return redirect()->route('dashboard');
        }

        // Credenziali di test per jtt 
        // if ($request->email === 'jtt@jtt.it' && $request->password === 'jtt') {
        //     $user = User::findOrFail(2);
        //     Log::info('Accesso effettuato con credenziali di test', [
        //         'user_id' => 2,
        //         'role' => 'publisher',
        //     ]);
        //     Auth::login($user, $request->filled('remember'));
        //     $request->session()->regenerate();
        //     return redirect()->route('dashboard');
        // }

        // Credenziali di test per il publisher 
        // if ($request->email === 'publisher@publisher.it' && $request->password === 'publisher') {
        //     $user = User::findOrFail(3);
        //     Log::info('Accesso effettuato con credenziali di test', [
        //         'user_id' => 3,
        //         'role' => 'publisher',
        //     ]);
        //     Auth::login($user, $request->filled('remember'));
        //     $request->session()->regenerate();
        //     return redirect()->route('dashboard');
        // }

        // Rate Limiting con protezione avanzata
        $key = Str::lower($request->email) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            Log::warning('Troppi tentativi di accesso IP limitato', [
                'email' => hash('sha256', $request->email),
                'ip' => $request->ip(),
                'blocked_for_seconds' => $seconds
            ]);

            return back()
                ->with('error', "Troppi tentativi di accesso. Riprova tra " . ceil($seconds / 60) . " minuti.")
                ->withInput($request->except('password'));
        }

        // Verifica se l'utente esiste
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            RateLimiter::hit($key, 15 * 60); // 15 minuti di penalità

            Log::warning('Tentativo di accesso con utente non esistente', [
                'email' => hash('sha256', $request->email),
                'ip' => $request->ip()
            ]);

            return back()
                ->with('error', 'Le credenziali inserite non sono corrette.')
                ->withInput($request->except('password'));
        }

        // Verifica se l'account è bloccato
        if ($user->isLockedOut()) {
            $minutesLeft = floor(now()->diffInMinutes($user->locked_until));

            Log::warning('Tentativo di accesso su account bloccato', [
                'user_id' => $user->id,
                'locked_until' => $user->locked_until
            ]);

            return back()
                ->with('error', "Account temporaneamente bloccato. Riprova tra {$minutesLeft} minuti.")
                ->withInput($request->except('password'));
        }

        // Verifica le credenziali
        if (!Auth::attempt($credentials, $request->filled('remember'))) {
            RateLimiter::hit($key, 15 * 60); // 15 minuti di penalità
            $user->incrementLoginAttempts();

            Log::warning('Tentativo di accesso fallito per credenziali errate', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'attempts' => $user->failed_login_attempts
            ]);

            return back()
                ->with('error', 'Le credenziali inserite non sono corrette.')
                ->withInput($request->except('password'));
        }

        // Reset dei tentativi dopo login riuscito
        RateLimiter::clear($key);
        $user->resetLoginAttempts();

        // Verifica email validata
        if (!$user->email_verified) {
            Auth::logout();

            Log::warning('Tentativo di accesso con email non verificata', [
                'user_id' => $user->id
            ]);

            return back()
                ->with('warning', 'Verifica il tuo indirizzo email prima di accedere.')
                ->withInput($request->except('password'));
        }

        // Verifica validazione admin
        if (!$user->is_validated) {
            Auth::logout();

            Log::warning('Tentativo di accesso con account non validato', [
                'user_id' => $user->id
            ]);

            return back()
                ->with('warning', 'Attendi che un admin verifichi il tuo account o scrivici a revenue@magellano.ai')
                ->withInput($request->except('password'));
        }

        // Configurazione sicurezza sessione
        config(['session.secure' => true]);
        config(['session.same_site' => 'strict']);

        // Login successful
        Auth::login($user, $request->filled('remember'));
        $request->session()->regenerate();

        // Verifica terms and conditions
        /* Temporaneamente disabilitato
        if (!$user->hasAcceptedTerms()) {
            return redirect()->route('terms.show');
        }   
        */

        Log::info('Accesso effettuato con successo', [
            'user_id' => $user->id,
            'role' => $user->role->code,
            'remember_me' => $request->filled('remember')
        ]);

        return redirect()->intended('dashboard');
    }

    public function logout(Request $request)
    {
        // Clear sensitive session data
        $request->session()->forget([
            'auth.ip',
            'auth.user_agent',
            'auth.last_active'
        ]);

        $userId = Auth::id();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('User logged out', [
            'user_id' => $userId,
            'ip' => $request->ip()
        ]);

        return redirect()->route('login')
            ->with('success', 'Logout effettuato con successo.');
    }
}