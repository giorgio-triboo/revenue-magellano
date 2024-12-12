<?php

namespace App\Policies;

use App\Models\Publisher;
use App\Models\SubPublisher;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Log;

class SubPublisherPolicy
{
    use HandlesAuthorization;

    public function create(User $user)
    {
        Log::debug('SubPublisherPolicy@create chiamato', [
            'user_id' => $user->id,
            'user_role' => $user->role?->code
        ]);

        return $user->role?->code === 'admin' || $user->role?->code === 'publisher';
    }

    public function createFor(User $user, Publisher $publisher)
    {
        Log::debug('SubPublisherPolicy@createFor chiamato', [
            'user_id' => $user->id,
            'user_role' => $user->role?->code,
            'publisher_id' => $publisher->id
        ]);

        return $user->role?->code === 'admin' || $user->id === $publisher->user_id;
    }

    public function update(User $user, SubPublisher $subPublisher)
    {
        Log::debug('SubPublisherPolicy@update: Verifica autorizzazione', [
            'user_id' => $user->id,
            'user_role' => $user->role?->code,
            'sub_publisher_id' => $subPublisher->id,
            'publisher_id' => $subPublisher->publisher_id,
            'is_admin' => $user->role?->code === 'admin',
            'user_permissions' => $user->role?->toArray()
        ]);

        $isAuthorized = $user->role?->code === 'admin' || $user->id === $subPublisher->publisher->user_id;

        Log::debug('SubPublisherPolicy@update: Risultato autorizzazione', [
            'is_authorized' => $isAuthorized,
            'reason' => $isAuthorized ? 'Accesso autorizzato' : 'Accesso negato - Ruolo o proprietà non validi'
        ]);

        return $isAuthorized;
    }

    public function delete(User $user, SubPublisher $subPublisher)
    {
        Log::debug('SubPublisherPolicy@delete: Verifica autorizzazione', [
            'user_id' => $user->id,
            'user_role' => $user->role?->code,
            'sub_publisher_id' => $subPublisher->id,
            'is_admin' => $user->role?->code === 'admin',
            'user_permissions' => $user->role?->toArray()
        ]);

        $isAuthorized = $user->role?->code === 'admin' || $user->id === $subPublisher->publisher->user_id;

        Log::debug('SubPublisherPolicy@delete: Risultato autorizzazione', [
            'is_authorized' => $isAuthorized,
            'reason' => $isAuthorized ? 'Accesso autorizzato' : 'Accesso negato - Ruolo o proprietà non validi'
        ]);

        return $isAuthorized;
    }

    public function viewAny(User $user): bool
    {
        Log::debug('SubPublisherPolicy@viewAny check', ['user_id' => $user->id, 'role' => $user->role?->code]);
        return in_array($user->role?->code, ['admin', 'operator']);
    }

    public function view(User $user, SubPublisher $subPublisher): bool
    {
        Log::debug('SubPublisherPolicy@view check', ['user_id' => $user->id, 'sub_publisher_id' => $subPublisher->id]);
        return in_array($user->role?->code, ['admin', 'operator']);
    }
}
