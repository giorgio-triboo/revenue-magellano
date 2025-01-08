<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ResetPasswordController extends Controller
{
    public function show(Request $request)
    {
        try {
            $user = User::findOrFail($request->user);
            $email = $user->email;

            Log::info('Reset Password Request Details', [
                'user_id' => $user->id
            ]);

            $tokenData = DB::table('password_reset_tokens')
                ->where('email', $email)
                ->first();

            if (!$tokenData) {
                Log::error('Token non trovato nel database', [
                    'user_id' => $user->id
                ]);
                
                return redirect()->route('password.request')
                    ->with('error', 'Il token di reset non è più valido. Richiedine uno nuovo.');
            }

            $tokenCreatedAt = Carbon::parse($tokenData->created_at);
            $minutesElapsed = Carbon::now()->diffInMinutes($tokenCreatedAt);

            if ($minutesElapsed > 60) {
                DB::table('password_reset_tokens')
                    ->where('email', $email)
                    ->delete();

                Log::info('Token scaduto rimosso', [
                    'user_id' => $user->id,
                    'minutes_elapsed' => $minutesElapsed
                ]);

                return redirect()->route('password.request')
                    ->with('error', 'Il link per il reset della password è scaduto. Richiedine uno nuovo.');
            }

            return view('auth.password-reset', [
                'token' => $request->token,
                'email' => $email,
                'user_id' => $user->id
            ]);

        } catch (\Exception $e) {
            Log::error('Errore nella verifica del token', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('password.request')
                ->with('error', 'Si è verificato un errore nella verifica del token. Richiedine uno nuovo.');
        }
    }

    public function reset(Request $request)
    {
        Log::info('Inizio processo di reset password', [
            'user_id' => $request->user
        ]);
        try {
            $request->validate([
                'token' => 'required',
                'user' => 'required|exists:users,id',
                'password' => [
                    'required',
                    'confirmed',
                    'min:8',
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/'
                ],
            ], [
                'password.regex' => 'La password deve contenere almeno una lettera maiuscola, una minuscola, un numero e un carattere speciale.'
            ]);

            $user = User::findOrFail($request->user);
            $email = $user->email;

            Log::info('Password Reset Attempt', [
                'user_id' => $user->id
            ]);

            // Verifica token
            $tokenData = DB::table('password_reset_tokens')
                ->where('email', $email)
                ->where('token', $request->token)
                ->first();

            if (!$tokenData) {
                Log::error('Token non valido o non trovato', [
                    'user_id' => $user->id
                ]);
                return redirect()
                    ->route('password.request')
                    ->with('error', 'Token non valido. Richiedine uno nuovo.');
            }

            // Verifica scadenza token
            $tokenCreatedAt = Carbon::parse($tokenData->created_at);
            if (Carbon::now()->diffInMinutes($tokenCreatedAt) > 60) {
                DB::table('password_reset_tokens')
                    ->where('email', $email)
                    ->delete();
                    
                return redirect()
                    ->route('password.request')
                    ->with('error', 'Il token è scaduto. Richiedine uno nuovo.');
            }

            // Verifica se la nuova password è uguale alla precedente
            if (Hash::check($request->password, $user->password)) {
                Log::warning('Tentativo di utilizzare la stessa password', [
                    'user_id' => $user->id
                ]);
                return back()
                    ->withInput()
                    ->withErrors(['password' => 'La nuova password non può essere uguale alla precedente.']);
            }

            Log::info('Inizio transazione database', [
                'user_id' => $user->id
            ]);
            
            DB::beginTransaction();
            try {
                // Aggiorna la password
                $newPassword = Hash::make($request->password);
                Log::info('Password hashata correttamente', [
                    'user_id' => $user->id
                ]);

                $user->password = $newPassword;
                $user->remember_token = Str::random(60);
                $user->save();

                Log::info('Password utente aggiornata', [
                    'user_id' => $user->id
                ]);

                // Rimuovi il token di reset
                DB::table('password_reset_tokens')
                    ->where('email', $email)
                    ->delete();

                Log::info('Token di reset rimosso', [
                    'user_id' => $user->id
                ]);

                DB::commit();

                // Trigger dell'evento di reset password
                event(new PasswordReset($user));
                
                Log::info('Password reset completato con successo', [
                    'user_id' => $user->id
                ]);

                return redirect()
                    ->route('login')
                    ->with('status', 'La password è stata reimpostata con successo!');

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Errore durante la transazione di reset password', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Errore durante il reset della password', [
                'user_id' => $request->user ?? null,
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);

            $errorMessage = 'Si è verificato un errore durante il reset della password.';
            
            // Gestione errori specifici
            if ($e instanceof \Illuminate\Database\QueryException) {
                $errorMessage = 'Errore durante il salvataggio della password. Riprova.';
            } elseif ($e instanceof \PDOException) {
                $errorMessage = 'Errore di connessione al database. Riprova più tardi.';
            }

            return back()
                ->withInput()
                ->withErrors(['password' => $errorMessage]);
        }
    }
}