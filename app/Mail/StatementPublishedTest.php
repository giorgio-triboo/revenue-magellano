<?php

namespace App\Mail;

class StatementPublishedTest extends StatementPublished
{
    public function build()
    {
        return $this->markdown('emails.uploads.statement-published')
                    ->subject('[TEST] ' . config('app.name') . ' - Consuntivo Pubblicato' );
    }
}