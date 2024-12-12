<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubPublisher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'publisher_id',
        'display_name',
        'invoice_group',
        'ax_name',
        'channel_detail',
        'notes',
        'is_active',
        'is_primary'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_primary' => 'boolean'
    ];

    // Relazioni
    public function publisher()
    {
        return $this->belongsTo(Publisher::class);
    }

    // Attributi accessori
    public function getStatusLabelAttribute()
    {
        return $this->is_active ? 'Attivo' : 'Non attivo';
    }

    public function getIsPrimaryLabelAttribute()
    {
        return $this->is_primary ? 'Primario' : 'Secondario';
    }

    // Query Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    // Helper methods
    public static function generateUniqueCode($publisher)
    {
        $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $publisher->company_name), 0, 3));
        $count = $publisher->subPublishers()->count() + 1;
        return sprintf('%s%03d', $prefix, $count);
    }
}