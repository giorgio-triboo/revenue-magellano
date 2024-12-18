<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AxData extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ax_data';

    protected $fillable = [
        'publisher_id',
        'ax_vend_account',
        'ax_vend_id',
        'vend_group',
        'party_type',
        'tax_withhold_calculate',
        'item_id',
        'ax_vat_number',
        'email',
        'cost_profit_center',
        'state',
        'state_id',
        'county',
        'county_id',
        'city',
        'postal_code',
        'address',
        'payment',
        'payment_mode',
        'currency_code',
        'sales_tax_group',
        'number_sequence_group_id'
    ];

    // Relazione con Publisher
    public function Publisher()
    {
        return $this->belongsTo(Publisher::class);
    }
}
