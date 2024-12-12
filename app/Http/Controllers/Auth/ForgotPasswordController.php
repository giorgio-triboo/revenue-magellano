<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ForgotPasswordController extends Controller
{
    public function show()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ], [
            'email.exists' => 'Non troviamo un utente con questo indirizzo email.'
        ]);

        try {
            // Verifica se esiste già un token valido
            $existingToken = DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->first();

            if ($existingToken) {
                $tokenCreatedAt = Carbon::parse($existingToken->created_at);
                
                // Se il token esistente non è scaduto (meno di 60 minuti)
                if (Carbon::now()->diffInMinutes($tokenCreatedAt) < 60) {
                    Log::info('Tentativo di richiesta reset password con token già attivo', [
                        'email' => $request->email,
                        'existing_token_created_at' => $tokenCreatedAt
                    ]);

                    return back()->with('info', 
                        'È già presente una richiesta di reset password attiva. ' .
                        'Controlla la tua email prima di richiederne una nuova.'
                    );
                }

                // Se il token è scaduto, lo eliminiamo
                DB::table('password_reset_tokens')
                    ->where('email', $request->email)
                    ->delete();
            }

            $status = Password::sendResetLink(
                $request->only('email')
            );

            Log::info('Richiesta reset password', [
                'email' => $request->email,
                'status' => $status
            ]);

            return $status === Password::RESET_LINK_SENT
                ? back()->with('success', 'Ti abbiamo inviato una email con il link per reimpostare la password!')
                : back()->withErrors(['email' => __($status)]);

        } catch (\Exception $e) {
            Log::error('Errore nell\'invio del link di reset password', [
                'email' => $request->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors(['email' => 'Si è verificato un errore nell\'invio dell\'email.']);
        }
    }
}