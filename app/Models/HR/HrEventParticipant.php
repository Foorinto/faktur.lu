<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrEventParticipant extends Model
{
    use HasFactory;

    public const RESPONSES = ['pending', 'accepted', 'declined'];

    protected $fillable = [
        'hr_event_id',
        'employee_id',
        'external_email',
        'response',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(HrEvent::class, 'hr_event_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->employee) {
            return trim($this->employee->first_name.' '.$this->employee->last_name);
        }

        return (string) ($this->external_email ?? '');
    }
}
