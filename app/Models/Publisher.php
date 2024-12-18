<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Publisher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vat_number',
        'company_name',
        'legal_name',
        'state',
        'state_id',
        'county',
        'county_id',
        'city',
        'postal_code',
        'address',
        'iban',
        'swift',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    // Attributi accessori
    public function getNameAttribute()
    {
        return $this->legal_name;
    }

    public function getStatusLabelAttribute()
    {
        return $this->is_active ? 'Attivo' : 'Non attivo';
    }

    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at->format('d/m/Y H:i');
    }

    public function getFormattedUpdatedAtAttribute()
    {
        return $this->updated_at->format('d/m/Y H:i');
    }

    // Relazioni
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function subPublishers()
    {
        return $this->hasMany(SubPublisher::class);
    }

    // Query Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeFilterBySearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('company_name', 'like', "%{$search}%")
                ->orWhere('vat_number', 'like', "%{$search}%")
                ->orWhere('legal_name', 'like', "%{$search}%");
        });
    }

    public function scopeFilterByStatus($query, $status)
    {
        return $query->where('is_active', $status === 'active');
    }

    // Helper methods
    public function hasActiveSubPublishers()
    {
        return $this->subPublishers()->where('is_active', true)->exists();
    }

    public function activeSubPublishers()
    {
        return $this->subPublishers()->where('is_active', true);
    }

    public function createSubPublisher(array $data)
    {
        return $this->subPublishers()->create($data);
    }

    // Metodi per l'export
    public static function getExportableFields()
    {
        return [
            'id' => 'ID',
            'company_name' => 'Nome Azienda',
            'legal_name' => 'Ragione Sociale',
            'vat_number' => 'Partita IVA',
            'county' => 'Provincia',
            'city' => 'Città',
            'postal_code' => 'CAP',
            'iban' => 'IBAN',
            'swift' => 'SWIFT',
            'is_active' => 'Stato',
            'created_at' => 'Data Creazione',
            'updated_at' => 'Ultimo Aggiornamento'
        ];
    }

    public function toExportArray()
    {
        return [
            'id' => $this->id,
            'company_name' => $this->company_name,
            'legal_name' => $this->legal_name,
            'vat_number' => $this->vat_number,
            'county' => $this->county,
            'city' => $this->city,
            'postal_code' => $this->postal_code,
            'iban' => $this->iban,
            'swift' => $this->swift,
            'is_active' => $this->status_label,
            'created_at' => $this->formatted_created_at,
            'updated_at' => $this->formatted_updated_at
        ];
    }

    public function axData()
    {
        return $this->hasOne(AxData::class);
    }

}