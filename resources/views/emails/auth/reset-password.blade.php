@component('mail::message')
# Reset della Password

Ciao {{ $user->first_name }},

Hai richiesto il **reset della password** per il tuo account. **Clicca sul pulsante** qui sotto per procedere:

@component('mail::button', ['url' => $resetUrl])
Reset Password
@endcomponent

Il link per il reset della password scadrà tra {{ config('auth.passwords.users.expire', 60) }} minuti.

Se non hai richiesto il reset della password, puoi ignorare questa email.

Grazie,<br>
{{ config('app.name') }}

@component('mail::subcopy')
Se hai problemi con il pulsante "Reset Password", copia e incolla questo URL nel tuo browser:<br>
{{ $resetUrl }}
@endcomponent
@endcomponent