<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Publisher;
use Illuminate\Auth\Access\HandlesAuthorization;

class PublisherPolicy
{
    use HandlesAuthorization;

    /**
     * Determina se l'utente può vedere la lista di publisher.
     * Solo Admin e Operative possono vedere la lista
     */
    public function viewAny(User $user)
    {
        return $user->isAdmin();
    }

    /**
     * Determina se l'utente può vedere i dettagli di un publisher specifico
     * Solo Admin e Operative possono vedere i dettagli nella sezione publisher
     */
    public function view(User $user, Publisher $publisher)
    {
        return $user->isAdmin();
    }

    /**
     * Determina se l'utente può modificare il publisher
     * Solo Admin può modificare
     */
    public function update(User $user, Publisher $publisher)
    {
        return $user->isAdmin();
    }

    /**
     * Determina se l'utente può gestire gli utenti del publisher
     * Solo Admin può gestire gli utenti
     */
    public function manageUsers(User $user, Publisher $publisher)
    {
        return $user->isAdmin();
    }

    /**
     * Determina se l'utente può inviare email di reset password
     * Solo Admin può inviare email di reset
     */
    public function sendPasswordReset(User $user, Publisher $publisher)
    {
        return $user->isAdmin();
    }

    /**
     * Determina se l'utente può esportare i dati dei publisher
     * Solo Admin può esportare i dati
     * 
     * @param User $user
     * @return bool
     */
    public function export(User $user)
    {
        return $user->isAdmin();
    }

    /**
     * Determina se l'utente può accedere ai dati sensibili di un publisher
     * Solo Admin può vedere dati sensibili come IBAN e SWIFT
     * 
     * @param User $user
     * @return bool
     */
    public function viewSensitiveData(User $user)
    {
        return $user->isAdmin();
    }
    
}