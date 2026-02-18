<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'status',
        'metadata',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Action types constants.
     */
    public const ACTION_LOGIN = 'auth.login';
    public const ACTION_LOGOUT = 'auth.logout';
    public const ACTION_LOGIN_FAILED = 'auth.failed';
    public const ACTION_PASSWORD_CHANGED = 'auth.password_changed';
    public const ACTION_2FA_ENABLED = 'auth.2fa_enabled';
    public const ACTION_2FA_DISABLED = 'auth.2fa_disabled';

    /**
     * Status constants.
     */
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';

    /**
     * Action labels for display.
     */
    public static array $actionLabels = [
        'auth.login' => 'Connexion',
        'auth.logout' => 'Déconnexion',
        'auth.failed' => 'Échec de connexion',
        'auth.password_changed' => 'Mot de passe modifié',
        'auth.2fa_enabled' => '2FA activée',
        'auth.2fa_disabled' => '2FA désactivée',
        'Invoice.created' => 'Facture créée',
        'Invoice.updated' => 'Facture modifiée',
        'Invoice.deleted' => 'Facture supprimée',
        'Invoice.finalized' => 'Facture finalisée',
        'Invoice.sent' => 'Facture envoyée',
        'Client.created' => 'Client créé',
        'Client.updated' => 'Client modifié',
        'Client.deleted' => 'Client supprimé',
        'Quote.created' => 'Devis créé',
        'Quote.updated' => 'Devis modifié',
        'Quote.deleted' => 'Devis supprimé',
        'Quote.converted' => 'Devis converti en facture',
        'Expense.created' => 'Dépense créée',
        'Expense.updated' => 'Dépense modifiée',
        'Expense.deleted' => 'Dépense supprimée',
        'TimeEntry.created' => 'Entrée temps créée',
        'TimeEntry.updated' => 'Entrée temps modifiée',
        'TimeEntry.deleted' => 'Entrée temps supprimée',
        'BusinessSettings.updated' => 'Paramètres modifiés',
        'export.faia' => 'Export FAIA généré',
        'export.pdf' => 'PDF téléchargé',
        'peppol_sent' => 'Envoi Peppol réussi',
        'peppol_failed' => 'Envoi Peppol échoué',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = now();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get human-readable action label.
     */
    public function getActionLabelAttribute(): string
    {
        return self::$actionLabels[$this->action] ?? $this->action;
    }

    /**
     * Get emoji for action type.
     */
    public function getActionEmojiAttribute(): string
    {
        return match (true) {
            str_starts_with($this->action, 'auth.login') => '🔐',
            str_starts_with($this->action, 'auth.logout') => '🚪',
            str_starts_with($this->action, 'auth.failed') => '❌',
            str_starts_with($this->action, 'auth.') => '🔑',
            str_contains($this->action, 'Invoice') => '📄',
            str_contains($this->action, 'Client') => '👤',
            str_contains($this->action, 'Quote') => '📋',
            str_contains($this->action, 'Expense') => '💰',
            str_contains($this->action, 'TimeEntry') => '⏱️',
            str_contains($this->action, 'BusinessSettings') => '⚙️',
            str_contains($this->action, 'export') => '📊',
            str_contains($this->action, 'peppol') => '🌐',
            default => '📝',
        };
    }

    /**
     * Scope to filter by action type.
     */
    public function scopeOfAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to filter by action category.
     */
    public function scopeOfCategory(Builder $query, string $category): Builder
    {
        return $query->where('action', 'like', $category . '.%');
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeBetweenDates(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to . ' 23:59:59');
        }
        return $query;
    }

    /**
     * Scope to filter by auditable type.
     */
    public function scopeForModel(Builder $query, string $type, ?int $id = null): Builder
    {
        $query->where('auditable_type', $type);
        if ($id) {
            $query->where('auditable_id', $id);
        }
        return $query;
    }

    /**
     * Scope to filter by status.
     */
    public function scopeOfStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Get changed fields between old and new values.
     */
    public function getChangedFieldsAttribute(): array
    {
        if (!$this->old_values || !$this->new_values) {
            return [];
        }

        $changes = [];
        $allKeys = array_unique(array_merge(
            array_keys($this->old_values),
            array_keys($this->new_values)
        ));

        foreach ($allKeys as $key) {
            $oldValue = $this->old_values[$key] ?? null;
            $newValue = $this->new_values[$key] ?? null;

            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changes;
    }
}
