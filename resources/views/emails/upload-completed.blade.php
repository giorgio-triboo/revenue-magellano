@component('mail::message')
<div class="header">
    <h1>Nuovo Upload Completato</h1>
</div>

<div class="details">
    <div class="details-item">
        <span class="details-label">File:</span>
        <span>{{ $details['original_filename'] }}</span>
    </div>
    <div class="details-item">
        <span class="details-label">Data processo:</span>
        <span>{{ $details['process_date'] }}</span>
    </div>
    <div class="details-item">
        <span class="details-label">Completato il:</span>
        <span>{{ $details['processed_at'] }}</span>
    </div>
    <div class="details-item">
        <span class="details-label">Record elaborati:</span>
        <span>{{ $details['total_records'] }}</span>
    </div>
</div>

@component('mail::button', ['url' => route('uploads.index', $upload->id)])
Visualizza Upload
@endcomponent

<div class="footer">
    Cordiali saluti,<br>
    {{ config('app.name') }}
</div>

@component('mail::subcopy')
Se hai problemi con il pulsante "Visualizza Upload", copia e incolla questo URL nel tuo browser:<br>
{{ route('uploads.index', $upload->id) }}
@endcomponent
@endcomponent