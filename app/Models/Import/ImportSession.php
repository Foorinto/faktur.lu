<?php

namespace App\Models\Import;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportSession extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'filename',
        'storage_path',
        'headers',
        'mapping',
        'preview_data',
        'total_rows',
        'valid_rows',
        'duplicate_rows',
        'error_rows',
        'imported_count',
        'updated_count',
        'skipped_count',
        'duplicate_strategy',
        'status',
        'errors',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'headers' => 'array',
        'mapping' => 'array',
        'preview_data' => 'array',
        'errors' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
