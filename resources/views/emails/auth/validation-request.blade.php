@component('mail::message')
# Nuova Richiesta di Validazione Publisher

Ciao Admin,

È stata ricevuta una nuova richiesta di validazione publisher.

**Dettagli Publisher:**
- Nome: {{ $user->first_name }} {{ $user->last_name }}
- Email: {{ $user->email }}
- Azienda: {{ $user->publisher->company_name }}
- Data Registrazione: {{ $user->created_at->format('d/m/Y H:i') }}

@component('mail::button', ['url' => route('users.show', $user->id)])
Gestisci Richiesta
@endcomponent

Grazie,<br>
{{ config('app.name') }}
@endcomponent