@component('mail::message')
# Account Disattivato

Ciao {{ $user->first_name }},

Il tuo account è stato **disattivato**. 

Se ritieni che questo sia un errore o desideri riattivare il tuo account, invia una mail a pannello@triboo.direct.

Grazie,<br>
{{ config('app.name') }}
@endcomponent