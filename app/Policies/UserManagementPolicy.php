<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Log;

class UserManagementPolicy
{
    use HandlesAuthorization;
    public function viewAny(User $user): bool
    {
        Log::debug('UserManagementPolicy@viewAny check', ['user_id' => $user->id, 'role' => $user->role?->code]);
        return $user->role?->code === 'admin';
    }
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $targetUser): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, User $targetUser): bool
    {
        if (!$user->isAdmin()) {
            \Log::info('Autorizzazione negata: utente non è admin', [
                'user_id' => $user->id,
                'target_user_id' => $targetUser->id
            ]);
            return false;
        }

        if ($user->id === $targetUser->id) {
            \Log::info('Autorizzazione negata: admin non può eliminare se stesso', [
                'user_id' => $user->id
            ]);
            return false;
        }

        return true;
    }

    public function updateRole(User $user, User $targetUser): bool
    {
        if ($user->id === $targetUser->id) {
            return false;
        }

        return $user->role?->code === 'admin';
    }

    public function sendPasswordReset(User $user, User $targetUser): bool
    {
        return $user->role?->code === 'admin';
    }



    public function view(User $user, User $targetUser): bool
    {
        Log::debug('UserManagementPolicy@view check', ['user_id' => $user->id, 'target_user_id' => $targetUser->id]);
        return $user->role?->code === 'admin';
    }
}
