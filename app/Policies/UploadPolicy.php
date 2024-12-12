<?php
namespace App\Policies;

use App\Models\User;
use App\Models\FileUpload;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Log;

class UploadPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return in_array($user->role?->code, ['admin', 'operator']);
    }

    public function view(User $user, FileUpload $fileUpload): bool
    {
        return in_array($user->role?->code, ['admin', 'operator']) ||
            ($user->role?->code === 'publisher' && $fileUpload->user_id === $user->id);
    }

    public function create(User $user): bool
    {
        return in_array($user->role?->code, ['admin', 'operator']);
    }

    public function update(User $user, FileUpload $fileUpload): bool
    {
        return in_array($user->role?->code, ['admin', 'operator']);
    }

    public function delete(User $user, FileUpload $fileUpload): bool
    {
        if ($fileUpload->isPublished()) {
            return false;
        }
        return in_array($user->role?->code, ['admin', 'operator']);
    }

    public function export(User $user, FileUpload $fileUpload): bool
    {
        return in_array($user->role?->code, ['admin', 'operator']);
    }

    public function publish(User $user, FileUpload $fileUpload): bool
    {
        if (!$fileUpload->isCompleted()) {
            return false;
        }
        return in_array($user->role?->code, ['admin', 'operator']);
    }

    public function sendNotification(User $user, FileUpload $fileUpload): bool
    {
        if (!$fileUpload->isPublished()) {
            return false;
        }
        return in_array($user->role?->code, ['admin', 'operator']);
    }

    public function unpublish(User $user, FileUpload $fileUpload): bool
    {
        if (!$fileUpload->isPublished()) {
            return false;
        }
        return in_array($user->role?->code, ['admin', 'operator']);
    }
    public function list(User $user): bool
    {
        return false; // Nessuno può accedere
    }
}