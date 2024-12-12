<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SupportRequestNotification extends Mailable
{
    use Queueable, SerializesModels;

    protected $request;
    protected $user;

    public function __construct(array $request, $user)
    {
        $this->request = $request;
        $this->user = $user;
    }

    public function build()
    {
        return $this->markdown('emails.support-request')
            ->subject('Nuova richiesta di supporto')
            ->with([
                'request' => $this->request,
                'user' => $this->user,
                'details' => [
                    'category' => $this->request['category'],
                    'subject' => $this->request['subject'],
                    'description' => $this->request['description'],
                    'submitted_at' => now()->format('d/m/Y H:i'),
                    'user_name' => $this->user->full_name,
                    'publisher_name' => $this->user->publisher->company_name ?? 'N/A'
                ]
            ]);
    }
}