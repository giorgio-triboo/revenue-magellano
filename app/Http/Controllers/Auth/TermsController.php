<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TermsController extends Controller
{
    const CURRENT_TERMS_VERSION = '1.0';

    public function show()
    {
        $user = Auth::user();
        
        // Se l'utente ha già accettato i termini, redirect alla dashboard
        if ($user->hasAcceptedTerms()) {
            return redirect()->route('dashboard');
        }

        // Se l'utente non è attivo o verificato, logout e redirect al login
        if (!$user->isActiveAndVerified()) {
            Auth::logout();
            return redirect()->route('login')
                ->with('warning', 'Il tuo account deve essere verificato e attivato prima di poter accedere.');
        }

        return view('auth.terms');
    }

    public function accept(Request $request)
    {
        try {
            $request->validate([
                'accept_terms' => 'required|accepted'
            ], [
                'accept_terms.required' => 'Devi accettare i termini e le condizioni per continuare.',
                'accept_terms.accepted' => 'Devi accettare i termini e le condizioni per continuare.'
            ]);

            $user = Auth::user();
            
            Log::info('User accepting terms and conditions', [
                'user_id' => $user->id,
                'terms_version' => self::CURRENT_TERMS_VERSION
            ]);

            $user->update([
                'terms_accepted' => true,
                'terms_verified_at' => now(),
                'terms_version' => self::CURRENT_TERMS_VERSION
            ]);

            // Recupera l'URL intended se presente
            $redirectTo = session('url.intended', route('dashboard'));
            
            return redirect($redirectTo)
                ->with('success', 'Termini e condizioni accettati con successo.');

        } catch (ValidationException $e) {
            Log::debug('Validation failed for terms acceptance', [
                'user_id' => Auth::id(),
                'errors' => $e->errors()
            ]);

            return back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Error during terms acceptance', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return back()
                ->with('error', 'Si è verificato un errore durante l\'accettazione dei termini. Riprova.');
        }
    }
}