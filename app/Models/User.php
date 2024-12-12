<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\ResetPassword;
use App\Models\Role;
use App\Models\Publisher;

class User extends Authenticatable
{
    use HasFactory, SoftDeletes, Notifiable;

    protected $fillable = [
        'role_id',
        'first_name',
        'last_name',
        'email',
        'password',
        'publisher_id',
        'is_active',
        'activation_token',
        'email_verified',
        'email_verified_at',
        'privacy_accepted',
        'privacy_verified_at',
        'terms_accepted',
        'terms_verified_at',
        'terms_version',
        'can_receive_email',
        'is_validated',
        'failed_login_attempts',
        'locked_until',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'activation_token'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'terms_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'terms_accepted' => 'boolean',
        'privacy_accepted' => 'boolean',
        'can_receive_email' => 'boolean',
        'is_validated' => 'boolean',
        'locked_until' => 'datetime',
    ];

    /**
     * Metodo centralizzato per verificare e attivare l'account se necessario
     */
    public function validateAccount()
{
    $this->update([
        'is_validated' => true,
        'is_active' => true
    ]);

    Log::info('Account validato e attivato', [
        'user_id' => $this->id,
        'email' => $this->email
    ]);

    return true;
}

    public function deactivateAccount()
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Verifica se l'utente può ricevere email
     */
    public function canReceiveEmails(): bool
    {
        return $this->is_active && $this->can_receive_email;
    }

    public function isReadyForLogin()
    {
        return $this->email_verified &&
            $this->is_validated &&
            $this->is_active;
    }

    public function isPendingAdminValidation()
    {
        return $this->email_verified && !$this->is_validated;
    }

    public function markEmailAsVerified()
    {
        $this->update([
            'email_verified' => true,
            'email_verified_at' => $this->freshTimestamp(),
        ]);

        $this->checkAndActivateAccount();
        return true;
    }



    public function acceptTerms($version)
    {
        $this->update([
            'terms_accepted' => true,
            'terms_verified_at' => $this->freshTimestamp(),
            'terms_version' => $version
        ]);

        $this->checkAndActivateAccount();
        return true;
    }

    /**
     * Send the password reset notification.
     */
    public function sendPasswordResetNotification($token)
    {
        $url = url(route('password.reset', [
            'token' => $token,
            'email' => $this->email,
        ], false));

        Mail::to($this->email)->send(new ResetPassword($this, $url));
    }

    // Relazioni
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function publisher()
    {
        return $this->belongsTo(Publisher::class);
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function isAdmin()
    {
        return $this->role->code === 'admin';
    }

    public function isPublisher()
    {
        return $this->role->code === 'publisher';
    }

    public function canExportData()
    {
        return $this->isAdmin();
    }

    public function canViewSensitiveData()
    {
        return $this->isAdmin();
    }

    public function getRoleDisplayName()
    {
        return $this->role ? $this->role->name : 'N/A';
    }

    public function hasRole($roleName)
    {
        return $this->role && $this->role->name === $roleName;
    }

    public function hasAcceptedTerms(): bool
    {
        return $this->terms_accepted && $this->terms_verified_at !== null;
    }

    public function canAccessPlatform(): bool
    {
        return $this->isActiveAndVerified() && $this->hasAcceptedTerms();
    }

    public function isActiveAndVerified(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function isLockedOut(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    public function incrementLoginAttempts()
    {
        $this->failed_login_attempts++;

        if ($this->failed_login_attempts >= 5) {
            $this->locked_until = now()->addMinutes(15);
        }

        $this->save();
    }

    public function resetLoginAttempts()
    {
        $this->failed_login_attempts = 0;
        $this->locked_until = null;
        $this->save();
    }

    public static function getActiveAdmins()
    {
        return self::where('role_id', function($query) {
                $query->select('id')
                      ->from('roles')
                      ->where('code', 'admin');
            })
            ->where('publisher_id', 1)
            ->where('is_active', true)
            ->where('can_receive_email', true)
            ->get();
    }
}