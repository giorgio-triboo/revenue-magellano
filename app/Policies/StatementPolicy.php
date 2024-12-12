<?php

namespace App\Policies;

use App\Models\Statement;
use App\Models\User;

class StatementPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role?->code, ['admin', 'publisher']);
    }
    

    public function view(User $user, Statement $statement): bool
    {
        if (in_array($user->role?->code, ['admin', 'operator'])) {
            return true;
        }
    
        return $user->role?->code === 'publisher' && $statement->publisher_id === $user->publisher_id;
    }
    
    public function viewOwn(User $user): bool
    {
        return $user->role?->code === 'publisher';
    }
    
    public function download(User $user, Statement $statement): bool
    {
        return $this->view($user, $statement);
    }
    
}