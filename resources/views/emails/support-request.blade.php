@component('mail::message')
<div class="header">
    <h1>Nuova Richiesta di Supporto</h1>
</div>

<p>È stata ricevuta una nuova richiesta di supporto.</p>

<div class="details">
    <div class="details-item">
        <span class="details-label">Data richiesta:</span>
        <span>{{ $details['submitted_at'] }}</span>
    </div>
    <div class="details-item">
        <span class="details-label">Utente:</span>
        <span>{{ $details['user_name'] }}</span>
    </div>
    <div class="details-item">
        <span class="details-label">Publisher:</span>
        <span>{{ $details['publisher_name'] }}</span>
    </div>
    <div class="details-item">
        <span class="details-label">Categoria:</span>
        <span>{{ ucfirst($details['category']) }}</span>
    </div>
    <div class="details-item">
        <span class="details-label">Oggetto:</span>
        <span>{{ $details['subject'] }}</span>
    </div>
</div>
<br>
<br>
<div class="description">
    <h3>Descrizione:</h3>
    <p>{{ $details['description'] }}</p>
</div>

@endcomponent