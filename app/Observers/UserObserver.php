<?php

namespace App\Observers;

use App\Models\User;
use App\Mail\AccountDeactivated;
use App\Mail\AccountApproved;
use App\Mail\ValidationRequest;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    public function created(User $user)
    {
        if ($user->role?->code === 'publisher') {
            try {
                // Invia email a tutti gli admin
                $adminEmails = User::whereHas('role', function($query) {
                    $query->where('code', 'admin');
                })->pluck('email');

                foreach ($adminEmails as $adminEmail) {
                    Mail::to($adminEmail)->send(new ValidationRequest($user));
                }

                Log::info('Validation request email sent to admins', [
                    'user_id' => $user->id,
                    'admin_emails' => $adminEmails,
                    'publisher_id' => $user->publisher_id
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send validation request email', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
    }

    public function updated(User $user)
    {
        // Quando l'admin approva l'utente
        if ($user->isDirty('is_validated') && $user->is_validated) {
            try {
                Mail::to($user->email)->send(new AccountApproved($user));
                
                Log::info('Account approval email sent', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'publisher_id' => $user->publisher_id
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send account approval email', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        // Gestione disattivazione account
        if ($user->isDirty('is_active') && !$user->is_active) {
            try {
                Mail::to($user->email)->send(new AccountDeactivated($user));
                
                Log::info('Account deactivation email sent', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'publisher_id' => $user->publisher_id
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send account deactivation email', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
    }
}