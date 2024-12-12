<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class PlatformAccessPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can access the platform.
     *
     * @param User $user
     * @return Response|bool
     */
    public function access(?User $user): Response|bool
    {
        if (!$user) {
            return false;
        }

        if (!$user->is_active || !$user->email_verified_at) {
            return Response::deny('Il tuo account non è stato ancora verificato o attivato.');
        }

        if (!$user->hasAcceptedTerms()) {
            return Response::deny('Devi accettare i termini e le condizioni per continuare.');
        }

        return Response::allow();
    }
}