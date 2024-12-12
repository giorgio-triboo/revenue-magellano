<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AXData extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ax_data';

    protected $fillable = [
        'publisher_id',
        'ax_vend_account',
        'ax_vend_id',
        'country_id',
        'vend_group',
        'party_type',
        'tax_withhold_calculate',
        'item_id',
        'email',
        'cost_profit_center'
    ];

    // Relazione con Publisher
    public function Publisher()
    {
        return $this->belongsTo(Publisher::class);
    }
}