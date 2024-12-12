@component('mail::message')
<div class="header">
    <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" class="logo">
    <h1>File AX Pronto per il Download</h1>
</div>

<p>Il file AX relativo all'upload di {{ $details['user_name'] }} è stato generato ed è pronto per essere scaricato.</p>

<div class="details">
    <div class="details-item">
        <span class="details-label">Data processo:</span>
        <span>{{ $details['process_date'] }}</span>
    </div>
    <div class="details-item">
        <span class="details-label">Completato il:</span>
        <span>{{ $details['completed_at'] }}</span>
    </div>
    <div class="details-item">
        <span class="details-label">Percorso file:</span>
        <span>{{ $details['ax_export_path'] }}</span>
    </div>
</div>

@component('mail::button', ['url' => route('uploads.export', $upload->id)])
Scarica File AX
@endcomponent

<div class="footer">
    Cordiali saluti,<br>
    {{ config('app.name') }}
</div>

@component('mail::subcopy')
Se hai problemi con il pulsante "Scarica File AX", copia e incolla questo URL nel tuo browser:<br>
{{ route('uploads.export', $upload->id) }}
@endcomponent
@endcomponent