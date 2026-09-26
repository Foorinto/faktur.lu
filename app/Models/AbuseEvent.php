<?php

namespace App\Models;

use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un événement des protections anti-abus (FEAT-138), compté par le tableau
 * de bord d'administration. `detail` porte le domaine refusé ou la marque
 * imitée, jamais l'adresse complète.
 */
class AbuseEvent extends Model
{
    use MassPrunable;

    public const UPDATED_AT = null;

    public const TYPE_DISPOSABLE_EMAIL = 'disposable_email';

    public const TYPE_RESERVED_DOMAIN = 'reserved_domain';

    public const TYPE_BRAND_NAME_FLAGGED = 'brand_name_flagged';

    public const TYPE_COMPANY_NAME_REFUSED = 'company_name_refused';

    public const TYPE_TRIAL_QUOTA_REACHED = 'trial_quota_reached';

    /** Dans l'ordre d'affichage du tableau de bord. */
    public const TYPES = [
        self::TYPE_DISPOSABLE_EMAIL,
        self::TYPE_RESERVED_DOMAIN,
        self::TYPE_BRAND_NAME_FLAGGED,
        self::TYPE_COMPANY_NAME_REFUSED,
        self::TYPE_TRIAL_QUOTA_REACHED,
    ];

    protected $fillable = ['type', 'detail', 'user_id'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Purge par `monitoring:cleanup` : une tendance, pas une archive. */
    public function prunable()
    {
        return static::where('created_at', '<', now()->subDays((int) config('abuse.events_retention_days', 90)));
    }
}
