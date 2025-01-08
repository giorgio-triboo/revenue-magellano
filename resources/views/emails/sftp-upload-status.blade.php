@component('mail::message')
<div class="header">
    <h1>
        @if($upload->sftp_status === 'completed')
            Upload SFTP Completato con Successo
        @else
            Errore durante l'Upload SFTP
        @endif
    </h1>
</div>

<p>
    @if($upload->sftp_status === 'completed')
        Il file è stato caricato con successo sul server SFTP.
    @else
        Si è verificato un errore durante il caricamento del file sul server SFTP.
    @endif
</p>

<div class="details">
    <div class="details-item">
        <span class="details-label">Data processo:</span>
        <span>{{ $details['process_date'] }}</span>
    </div>
    <div class="details-item">
        <span class="details-label">Nome file:</span>
        <span>{{ $details['filename'] }}</span>
    </div>
    @if($upload->sftp_status === 'completed')
        <div class="details-item">
            <span class="details-label">Caricato il:</span>
            <span>{{ $details['uploaded_at'] }}</span>
        </div>
    @else
        <div class="details-item">
            <span class="details-label">Errore:</span>
            <span>{{ $details['error_message'] }}</span>
        </div>
    @endif
</div>

@if($upload->sftp_status === 'error')
    @component('mail::button', ['url' => route('uploads.uploadToSftp', $upload->id)])
    Riprova Upload SFTP
    @endcomponent
@endif

<div class="footer">
    Cordiali saluti,<br>
    {{ config('app.name') }}
</div>

@if($upload->sftp_status === 'error')
    @component('mail::subcopy')
    Se hai problemi con il pulsante "Riprova Upload SFTP", copia e incolla questo URL nel tuo browser:<br>
    {{ route('uploads.uploadToSftp', $upload->id) }}
    @endcomponent
@endif
@endcomponent