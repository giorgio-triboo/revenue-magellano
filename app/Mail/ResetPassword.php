<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class ResetPassword extends Mailable
{
    use Queueable, SerializesModels;

    public $resetUrl;
    public $user;

    /**
     * Create a new message instance.
     *
     * @param User $user
     * @param string $resetUrl
     * @return void
     */
    public function __construct(User $user, $resetUrl)
    {
        $this->user = $user;
        // Sostituisci l'email con l'ID utente nell'URL
        $this->resetUrl = str_replace('email=' . urlencode($user->email), 'user=' . $user->id, $resetUrl);
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.auth.reset-password')
                    ->subject('Reset Password - ' . config('app.name'));
    }
}