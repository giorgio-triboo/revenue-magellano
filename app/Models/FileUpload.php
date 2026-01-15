<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FileUpload extends Model
{
    use SoftDeletes;

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_PUBLISHED = 'published';
    const STATUS_ERROR = 'error';

    const AX_STATUS_PENDING = 'pending';
    const AX_STATUS_PROCESSING = 'processing';
    const AX_STATUS_COMPLETED = 'completed';
    const AX_STATUS_ERROR = 'error';

    const SFTP_STATUS_PENDING = 'pending';
    const SFTP_STATUS_PROCESSING = 'processing';
    const SFTP_STATUS_COMPLETED = 'completed';
    const SFTP_STATUS_ERROR = 'error';

    protected $fillable = [
        'user_id',
        'original_filename',
        'stored_filename',
        'mime_type',
        'file_size',
        'status',
        'process_date',
        'error_message',
        'total_records',
        'processed_records',
        'progress_percentage',
        'processing_stats',
        'published_at',
        'processed_at',
        'ax_export_status',
        'ax_export_path',
        'sftp_status',
        'sftp_error_message',
        'sftp_uploaded_at',
        'notification_sent_at'
    ];

    protected $casts = [
        'process_date' => 'date',
        'processed_at' => 'datetime',
        'published_at' => 'datetime',
        'sftp_uploaded_at' => 'datetime',
        'notification_sent_at' => 'datetime',
        'processing_stats' => 'array',
        'progress_percentage' => 'float',
        'total_records' => 'integer',
        'processed_records' => 'integer',
        'file_size' => 'integer'
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'progress_percentage' => 0,
        'processed_records' => 0,
        'processing_stats' => '[]',
        'ax_export_status' => self::AX_STATUS_PENDING
    ];

    /**
     * Nascondiamo le relazioni durante la serializzazione per evitare query aggiuntive
     */
    protected $hidden = [
        'user',
        'statements'
    ];


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function statements(): HasMany
    {
        return $this->hasMany(Statement::class);
    }

    // Helper Methods per lo stato
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function hasError(): bool
    {
        return $this->status === self::STATUS_ERROR;
    }

    public function canBePublished(): bool
    {
        return $this->isCompleted() && !$this->isPublished();
    }

    // Methods per le statistiche
    public function getTotalAmount(): float
    {
        return $this->statements()->sum('total_amount');
    }

    public function getTotalQuantity(): int
    {
        return $this->statements()->sum('validated_quantity');
    }

    public function getRevenueTypeStats(): array
    {
        return $this->statements()
            ->select('revenue_type')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(validated_quantity) as total_quantity')
            ->selectRaw('SUM(total_amount) as total_amount')
            ->groupBy('revenue_type')
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    $item->revenue_type => [
                        'count' => $item->count,
                        'total_quantity' => $item->total_quantity,
                        'total_amount' => $item->total_amount
                    ]
                ];
            })
            ->toArray();
    }

    public function updateProcessingStats(array $additionalStats = []): void
    {
        $stats = $this->processing_stats ?? [];
        $stats = array_merge($stats, $additionalStats, [
            'total_amount' => $this->getTotalAmount(),
            'total_quantity' => $this->getTotalQuantity(),
            'revenue_type_stats' => $this->getRevenueTypeStats(),
            'last_updated' => now()->toDateTimeString()
        ]);

        $this->update(['processing_stats' => $stats]);
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('process_date', $date);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_PROCESSING,
            self::STATUS_COMPLETED,
            self::STATUS_PUBLISHED
        ]);
    }

    public static function hasActiveUploadForDate($date): bool
    {
        return static::forDate($date)->active()->exists();
    }
    public function isAxPending(): bool
    {
        return $this->ax_export_status === self::AX_STATUS_PENDING;
    }

    public function isAxProcessing(): bool
    {
        return $this->ax_export_status === self::AX_STATUS_PROCESSING;
    }

    public function isAxCompleted(): bool
    {
        return $this->ax_export_status === self::AX_STATUS_COMPLETED;
    }

    /**
     * Restituisce solo i dati necessari per la serializzazione JSON nel frontend
     * Evita di caricare relazioni o accessor che causano query aggiuntive
     */
    public function toFrontendArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'process_date' => $this->process_date?->format('Y-m-d'),
            'progress_percentage' => $this->progress_percentage,
            'processed_records' => $this->processed_records,
            'total_records' => $this->total_records,
            'ax_export_status' => $this->ax_export_status,
            'ax_export_path' => $this->ax_export_path,
            'sftp_status' => $this->sftp_status,
            'sftp_error_message' => $this->sftp_error_message,
            'sftp_uploaded_at' => $this->sftp_uploaded_at?->toIso8601String(),
            'published_at' => $this->published_at?->toIso8601String(),
            'notification_sent_at' => $this->notification_sent_at?->toIso8601String(),
            'error_message' => $this->error_message,
            'processing_stats' => $this->processing_stats,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    public function isAxError(): bool
    {
        return $this->ax_export_status === self::AX_STATUS_ERROR;
    }

    public function canGenerateAxExport(): bool
    {
        return $this->isCompleted() && !$this->isAxProcessing() && !$this->isAxCompleted();
    }

}