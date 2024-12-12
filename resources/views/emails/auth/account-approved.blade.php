@component('mail::message')
# Account Approvato

Ciao {{ $user->first_name }},

Il tuo account è stato ***approvato con successo***. Ora puoi accedere a tutte le funzionalità della piattaforma.

@component('mail::button', ['url' => route('login')])
Accedi alla Piattaforma
@endcomponent

Se hai problemi con l'accesso o domande, non esitare a contattarci.

Grazie,<br>
{{ config('app.name') }}
@endcomponent