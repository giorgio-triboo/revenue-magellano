@component('mail::message')

@if ($user)
Buongiorno {{ $user->first_name }},
@else
Gentile utente,
@endif

ti confermiamo che è possibile inviare la fattura elettronica con codice destinatario **M5UXCR1** alla pec triboo.direct@legalmail.it o il preavviso di pagamento relativo **al mese di {{ $publishDetails['month'] }} {{ $publishDetails['year'] }}** all'indirizzo e-mail amministrazione.trd@triboo.it con gli importi maturati nel mese di {{ $publishDetails['month'] }} {{ $publishDetails['year'] }} e tutti gli eventuali altri mesi non ancora richiesti, consultando il pannello dedicato.

**Dettagli Aziendali:**
- Ragione Sociale: T-DIRECT S.R.L.
- Sede legale: Viale Sarca n. 336, Edificio Sedici – 20126 Milano
- Direzione/amministrazione: Viale Sarca n. 336, Edificio Sedici – 20126 Milano
- P.IVA Codice Fiscale: 09290830968
- R.E.A.: MI-2081245
- Codice SDI: M5UXCR1

**Indicare il mese di competenza e la Ragione Sociale, altrimenti non verrà accettata**

@component('mail::button', ['url' => route('login', $upload->id)])
Visualizza Consuntivo
@endcomponent

Grazie,<br>
{{ config('app.name') }}

@component('mail::subcopy')
Se hai problemi con il pulsante "Visualizza Consuntivo", copia e incolla questo URL nel tuo browser: {{ route('login', $upload->id) }}
@endcomponent
@endcomponent