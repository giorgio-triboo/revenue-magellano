<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Statement extends Model
{
    use SoftDeletes;

    const REVENUE_TYPE_CPL = 'cpl';
    const REVENUE_TYPE_CPC = 'cpc';
    const REVENUE_TYPE_CPM = 'cpm';
    const REVENUE_TYPE_TMK = 'tmk';
    const REVENUE_TYPE_CRG = 'crg';
    const REVENUE_TYPE_CPA = 'cpa';
    const REVENUE_TYPE_SMS = 'sms';


    protected $fillable = [
        'file_upload_id',
        'publisher_id',
        'sub_publisher_id',
        'campaign_name',
        'statement_year',
        'statement_month',
        'competence_year',
        'competence_month',
        'revenue_type',
        'validated_quantity',
        'pay_per_unit',
        'total_amount',
        'notes',
        'sending_date',
        'is_published',
        'published_at',
        'raw_data'
    ];

    protected $casts = [
        'statement_year' => 'integer',
        'statement_month' => 'integer',
        'competence_year' => 'integer',
        'competence_month' => 'integer',
        'validated_quantity' => 'integer',
        'pay_per_unit' => 'decimal:4',
        'total_amount' => 'decimal:4',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'raw_data' => 'array'
    ];

    protected $attributes = [
        'is_published' => false
    ];

    // Relationships
    public function fileUpload(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class)->withTrashed();
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Publisher::class);
    }

    public function subPublisher(): BelongsTo
    {
        return $this->belongsTo(SubPublisher::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeUnpublished($query)
    {
        return $query->where('is_published', false);
    }

    public function scopeForYear($query, $year)
    {
        return $query->where('statement_year', $year);
    }

    public function scopeForMonth($query, $month)
    {
        return $query->where('statement_month', $month);
    }

    public function scopeForCompetenceYear($query, $year)
    {
        return $query->where('competence_year', $year);
    }

    public function scopeForCompetenceMonth($query, $month)
    {
        return $query->where('competence_month', $month);
    }

    public function scopeByRevenueType($query, $type)
    {
        return $query->where('revenue_type', $type);
    }

    // Accessor Methods
    public function getStatementPeriodAttribute(): string
    {
        return sprintf('%d-%02d', $this->statement_year, $this->statement_month);
    }

    public function getCompetencePeriodAttribute(): string
    {
        return sprintf('%d-%02d', $this->competence_year, $this->competence_month);
    }

    // Helper Methods
    public function isValidRevenueType(string $type): bool
    {
        return in_array(strtolower($type), [
            self::REVENUE_TYPE_CPL,
            self::REVENUE_TYPE_CPC,
            self::REVENUE_TYPE_CPM,
            self::REVENUE_TYPE_TMK,
            self::REVENUE_TYPE_CRG,
            self::REVENUE_TYPE_CPA,
            self::REVENUE_TYPE_SMS
        ]);
    }


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($statement) {
            // Validazione del tipo di revenue
            if (!$statement->isValidRevenueType($statement->revenue_type)) {
                throw new \InvalidArgumentException('Invalid revenue type');
            }

            // Validazione della quantita
            if ($statement->validated_quantity < 0) {
                throw new \InvalidArgumentException('Quantity cannot be negative');
            }

            // Validazione del pagamento per unità
            if ($statement->pay_per_unit < 0) {
                throw new \InvalidArgumentException('Pay per unit cannot be negative');
            }

            // Validazione dell'importo totale
            if ($statement->total_amount < 0) {
                throw new \InvalidArgumentException('Total amount cannot be negative');
            }
        });
    }
}