<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function verify($token)
{
    $user = User::where('activation_token', $token)->first();

    if (!$user) {
        return redirect()->route('login')
                       ->with('error', 'Token di verifica non valido');
    }

    $user->email_verified = true;
    $user->activation_token = null;
    $user->email_verified_at = now();
    $user->save();

    return redirect()->route('login')
                    ->with('success', 'Email verificata con successo! Attendi che un amministratore verifichi il tuo account.');
}
}