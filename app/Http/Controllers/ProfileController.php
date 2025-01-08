<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;


class ProfileController extends Controller
{
    public function show()
    {
        $user = auth()->user();
        Log::info('Profile page accessed', [
            'user_id' => $user->id,
            'role' => $user->role->code
        ]);

        return view('profile.index', [
            'user' => $user,
            'publisher' => $user->publisher
        ]);
    }


    public function updateProfile(Request $request)
{
    try {
        $user = auth()->user();
        
        $validator = Validator::make($request->all(), [
            'first_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[\p{L}\s\-]+$/u'
            ],
            'last_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[\p{L}\s\-]+$/u'
            ],
        ], [
            'first_name.regex' => 'Il nome può contenere solo lettere, spazi e trattini',
            'last_name.regex' => 'Il cognome può contenere solo lettere, spazi e trattini',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        // Sanificazione aggiuntiva
        $validated['first_name'] = strip_tags($validated['first_name']);
        $validated['last_name'] = strip_tags($validated['last_name']);

        $user->update($validated);

        Log::info('Profile updated successfully', [
            'user_id' => $user->id,
            'fields' => array_keys($validated)
        ]);

        return back()->with('success', 'Profilo aggiornato con successo!');

    } catch (ValidationException $e) {
        Log::warning('Profile update validation failed', [
            'user_id' => auth()->id(),
            'errors' => $e->errors()
        ]);

        return back()
            ->withErrors($e->errors())
            ->withInput();

    } catch (\Exception $e) {
        Log::error('Error updating profile', [
            'user_id' => auth()->id(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return back()
            ->with('error', 'Si è verificato un errore durante l\'aggiornamento del profilo.')
            ->withInput();
    }
}


    

    public function toggleNotifications(Request $request)
    {
        try {
            $user = auth()->user();
            
            $user->update([
                'can_receive_email' => !$user->can_receive_email
            ]);

            Log::info('Email notifications toggled', [
                'user_id' => $user->id,
                'can_receive_email' => $user->can_receive_email
            ]);

            $status = $user->can_receive_email ? 'attivate' : 'disattivate';
            return back()->with('success', "Notifiche email $status con successo!");

        } catch (\Exception $e) {
            Log::error('Error toggling notifications', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Si è verificato un errore durante l\'aggiornamento delle preferenze email.');
        }
    }


    public function deactivateAccount()
    {
        try {
            $user = auth()->user();
            
            Log::info('Account deactivation requested', [
                'user_id' => $user->id
            ]);

            $user->deactivateAccount();
            
            Auth::logout();
            
            return redirect()->route('login')
                ->with('success', 'Account disattivato con successo. Contatta un amministratore per riattivarlo.');

        } catch (\Exception $e) {
            Log::error('Error deactivating account', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Si è verificato un errore durante la disattivazione dell\'account.');
        }
    }

}