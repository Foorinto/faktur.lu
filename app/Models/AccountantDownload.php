<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountantDownload extends Model
{
    use HasFactory;

    public const TYPE_FAIA = 'faia';
    public const TYPE_EXCEL = 'excel';
    public const TYPE_PDF_ARCHIVE = 'pdf_archive';

    public const TYPES = [
        self::TYPE_FAIA => 'Export FAIA (XML)',
        self::TYPE_EXCEL => 'Export Excel',
        self::TYPE_PDF_ARCHIVE => 'Archive PDF',
    ];

    protected $fillable = [
        'accountant_id',
        'user_id',
        'export_type',
        'period',
        'ip_address',
        'user_agent',
    ];

    /**
     * Get the accountant.
     */
    public function accountant(): BelongsTo
    {
        return $this->belongsTo(Accountant::class);
    }

    /**
     * Get the user (client).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record a download.
     */
    public static function record(
        Accountant $accountant,
        User $user,
        string $exportType,
        string $period,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): self {
        return self::create([
            'accountant_id' => $accountant->id,
            'user_id' => $user->id,
            'export_type' => $exportType,
            'period' => $period,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * Get export type label.
     */
    public function getExportTypeLabelAttribute(): string
    {
        return self::TYPES[$this->export_type] ?? $this->export_type;
    }
}
