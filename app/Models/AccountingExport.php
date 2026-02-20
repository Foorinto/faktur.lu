<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingExport extends Model
{
    use BelongsToUser;

    public const FORMAT_SAGE_BOB = 'sage_bob';
    public const FORMAT_SAGE_100 = 'sage_100';
    public const FORMAT_GENERIC = 'generic';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public const FORMATS = [
        self::FORMAT_GENERIC => 'CSV Générique',
        self::FORMAT_SAGE_BOB => 'Sage BOB 50 (ASCII)',
        self::FORMAT_SAGE_100 => 'Sage 100 (CSV)',
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
        'status',
        'error_message',
        'completed_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'stats' => 'array',
        'options' => 'array',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function markAsProcessing(): void
    {
        $this->update(['status' => self::STATUS_PROCESSING]);
    }

    public function markAsCompleted(string $filePath, string $fileName, array $stats): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'stats' => $stats,
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

        if ($start->month === 1 && $start->day === 1 && $end->month === 12 && $end->day === 31 && $start->year === $end->year) {
            return "Année {$start->year}";
        }

        if ($start->day === 1 && in_array($start->month, [1, 4, 7, 10])) {
            $quarter = ceil($start->month / 3);
            $endOfQuarter = $start->copy()->addMonths(3)->subDay();
            if ($end->equalTo($endOfQuarter)) {
                return "T{$quarter} {$start->year}";
            }
        }

        if ($start->month === 1 && $start->day === 1 && $end->month === 6 && $end->day === 30) {
            return "S1 {$start->year}";
        }
        if ($start->month === 7 && $start->day === 1 && $end->month === 12 && $end->day === 31) {
            return "S2 {$start->year}";
        }

        return $start->format('d/m/Y') . ' - ' . $end->format('d/m/Y');
    }
}
