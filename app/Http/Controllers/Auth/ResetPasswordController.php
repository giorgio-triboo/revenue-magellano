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
            // Ottieni l'utente dall'ID invece che dall'email
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

        try {
            $user = User::findOrFail($request->user);
            $email = $user->email;

            Log::info('Password Reset Attempt', [
                'user_id' => $user->id
            ]);

            $tokenData = DB::table('password_reset_tokens')
                ->where('email', $email)
                ->first();

            if (!$tokenData) {
                Log::error('Token non trovato durante il reset', [
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

                return redirect()->route('password.request')
                    ->with('error', 'Il link per il reset della password è scaduto. Richiedine uno nuovo.');
            }

            if (Hash::check($request->password, $user->password)) {
                return back()->withErrors(['password' => 'La nuova password non può essere uguale alla precedente.']);
            }

            $status = Password::reset(
                [
                    'email' => $email,
                    'password' => $request->password,
                    'password_confirmation' => $request->password_confirmation,
                    'token' => $request->token
                ],
                function ($user, $password) {
                    $user->forceFill([
                        'password' => Hash::make($password),
                        'remember_token' => Str::random(60),
                    ])->save();

                    DB::table('password_reset_tokens')
                        ->where('email', $user->email)
                        ->delete();

                    event(new PasswordReset($user));
                    Log::info('Password reset completato con successo', ['user_id' => $user->id]);
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return redirect()->route('login')
                    ->with('password_reset_success', 'La password è stata reimpostata con successo!');
            }

            return back()->withErrors(['email' => __($status)]);

        } catch (\Exception $e) {
            Log::error('Errore durante il reset della password', [
                'user_id' => $request->user ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors(['email' => 'Si è verificato un errore durante il reset della password.']);
        }
    }
}