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
        'vend_group',
        'party_type',
        'tax_withhold_calculate',
        'item_id',
        'ax_vat_number',
        'email',
        'cost_profit_center',
        'address_country',
        'address_country_id',
        'address_county',
        'address_county_id',
        'address_city',
        'address_city_zip',
        'address_street',
        'payment',
        'payment_mode',
        'currency_code',
        'SalesTaxGroupCode',
        'NumberSequenceGroupId'
    ];

    // Relazione con Publisher
    public function Publisher()
    {
        return $this->belongsTo(Publisher::class);
    }
}
