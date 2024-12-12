@component('mail::message')
# Verifica il tuo account

Ciao {{ $user->first_name }},

Grazie per esserti registrato. Per **completare la registrazione**, **clicca sul pulsante** qui sotto.

@component('mail::button', ['url' => $verificationUrl])
Verifica Account
@endcomponent

Se non hai creato tu questo account, ignora questa email.

Grazie,<br>
{{ config('app.name') }}
@endcomponent