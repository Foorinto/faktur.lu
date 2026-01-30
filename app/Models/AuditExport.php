<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditExport extends Model
{
    use HasFactory;

    public const FORMAT_CSV = 'csv';
    public const FORMAT_JSON = 'json';
    public const FORMAT_XML = 'xml';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public const FORMATS = [
        self::FORMAT_CSV => 'CSV (Tableur)',
        self::FORMAT_JSON => 'JSON (Développeurs)',
        self::FORMAT_XML => 'XML FAIA (AED Luxembourg)',
    ];

    protected $fillable = [
        'user_id',
        'period_start',
        'period_end',
        'format',
        'file_path',
        'file_name',
        'stats',
        'options',
        'sequence_valid',
        'sequence_errors',
        'status',
        'error_message',
        'completed_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'stats' => 'array',
        'options' => 'array',
        'sequence_valid' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

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

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function markAsProcessing(): void
    {
        $this->update(['status' => self::STATUS_PROCESSING]);
    }

    public function markAsCompleted(string $filePath, string $fileName, array $stats, bool $sequenceValid, ?string $sequenceErrors = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'stats' => $stats,
            'sequence_valid' => $sequenceValid,
            'sequence_errors' => $sequenceErrors,
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
        ]);
    }

    public function getFormatLabelAttribute(): string
    {
        return self::FORMATS[$this->format] ?? $this->format;
    }

    public function getDocumentsCountAttribute(): int
    {
        if (!$this->stats) {
            return 0;
        }

        return ($this->stats['invoices_count'] ?? 0) + ($this->stats['credit_notes_count'] ?? 0);
    }

    public function getPeriodLabelAttribute(): string
    {
        $start = $this->period_start;
        $end = $this->period_end;

        // Full year
        if ($start->month === 1 && $start->day === 1 && $end->month === 12 && $end->day === 31 && $start->year === $end->year) {
            return "Année {$start->year}";
        }

        // Quarter
        if ($start->day === 1 && in_array($start->month, [1, 4, 7, 10])) {
            $quarter = ceil($start->month / 3);
            $endOfQuarter = $start->copy()->addMonths(3)->subDay();
            if ($end->equalTo($endOfQuarter)) {
                return "T{$quarter} {$start->year}";
            }
        }

        // Semester
        if ($start->month === 1 && $start->day === 1 && $end->month === 6 && $end->day === 30) {
            return "S1 {$start->year}";
        }
        if ($start->month === 7 && $start->day === 1 && $end->month === 12 && $end->day === 31) {
            return "S2 {$start->year}";
        }

        // Custom period
        return $start->format('d/m/Y') . ' - ' . $end->format('d/m/Y');
    }
}
