<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class EmailSettings extends Model
{
    use HasFactory, BelongsToUser;

    public const PROVIDER_FAKTUR = 'faktur';
    public const PROVIDER_SMTP = 'smtp';
    public const PROVIDER_BREVO = 'brevo';
    public const PROVIDER_POSTMARK = 'postmark';
    public const PROVIDER_RESEND = 'resend';

    public const PROVIDERS = [
        self::PROVIDER_FAKTUR => 'faktur.lu (recommandé)',
        self::PROVIDER_SMTP => 'Serveur SMTP personnel',
        self::PROVIDER_BREVO => 'Brevo (Sendinblue)',
        self::PROVIDER_POSTMARK => 'Postmark',
        self::PROVIDER_RESEND => 'Resend',
    ];

    protected $fillable = [
        'user_id',
        'provider',
        'provider_config',
        'from_address',
        'from_name',
        'provider_verified',
        'last_test_at',
        'default_message',
        'signature',
        'send_copy_to_self',
        'reminders_enabled',
        'reminder_levels',
    ];

    protected $casts = [
        'send_copy_to_self' => 'boolean',
        'reminders_enabled' => 'boolean',
        'reminder_levels' => 'array',
        'provider_verified' => 'boolean',
        'last_test_at' => 'datetime',
    ];

    protected $hidden = [
        'provider_config',
    ];

    /**
     * Default reminder levels configuration.
     */
    public static function defaultReminderLevels(): array
    {
        return [
            1 => [
                'enabled' => true,
                'days_after_due' => 7,
                'subject' => 'Rappel : Facture {numero} en attente de paiement',
                'body' => "Bonjour {client_nom},\n\nNous nous permettons de vous rappeler que la facture {numero} d'un montant de {montant} € est arrivée à échéance le {date_echeance}.\n\nNous vous remercions de bien vouloir procéder au règlement dans les meilleurs délais.\n\nCordialement,\n{entreprise}",
            ],
            2 => [
                'enabled' => true,
                'days_after_due' => 14,
                'subject' => 'Relance : Facture {numero} impayée',
                'body' => "Bonjour {client_nom},\n\nMalgré notre précédent rappel, nous constatons que la facture {numero} d'un montant de {montant} € reste impayée.\n\nLe retard de paiement est actuellement de {jours_retard} jours.\n\nNous vous prions de régulariser cette situation dans les plus brefs délais.\n\nCordialement,\n{entreprise}",
            ],
            3 => [
                'enabled' => true,
                'days_after_due' => 30,
                'subject' => 'Mise en demeure : Facture {numero}',
                'body' => "Bonjour {client_nom},\n\nMalgré nos relances précédentes, la facture {numero} d'un montant de {montant} € demeure impayée depuis {jours_retard} jours.\n\nSans règlement de votre part sous 8 jours, nous nous verrons contraints d'engager les procédures de recouvrement appropriées.\n\nCordialement,\n{entreprise}",
            ],
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get reminder level configuration.
     */
    public function getReminderLevel(int $level): ?array
    {
        $levels = $this->reminder_levels ?? self::defaultReminderLevels();
        return $levels[$level] ?? null;
    }

    /**
     * Get or create settings for a user.
     */
    public static function getOrCreate(User $user): self
    {
        return self::firstOrCreate(
            ['user_id' => $user->id],
            [
                'provider' => self::PROVIDER_FAKTUR,
                'reminder_levels' => self::defaultReminderLevels(),
            ]
        );
    }

    /**
     * Set the provider configuration (encrypted).
     */
    public function setProviderConfigAttribute(?array $value): void
    {
        if ($value === null) {
            $this->attributes['provider_config'] = null;
            return;
        }

        $this->attributes['provider_config'] = Crypt::encryptString(json_encode($value));
    }

    /**
     * Get the provider configuration (decrypted).
     */
    public function getProviderConfigAttribute(?string $value): ?array
    {
        if ($value === null) {
            return null;
        }

        try {
            return json_decode(Crypt::decryptString($value), true);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get the effective from address.
     */
    public function getEffectiveFromAddress(): string
    {
        if ($this->from_address) {
            return $this->from_address;
        }

        return config('mail.from.address') ?: config('marque.email_expediteur');
    }

    /**
     * Get the effective from name.
     */
    public function getEffectiveFromName(): ?string
    {
        if ($this->from_name) {
            return $this->from_name;
        }

        return $this->user?->businessSettings?->company_name
            ?? config('mail.from.name');
    }

    /**
     * Check if using a custom provider.
     */
    public function hasCustomProvider(): bool
    {
        return $this->provider !== self::PROVIDER_FAKTUR;
    }

    /**
     * Check if provider requires configuration.
     */
    public function requiresConfiguration(): bool
    {
        return in_array($this->provider, [
            self::PROVIDER_SMTP,
            self::PROVIDER_BREVO,
            self::PROVIDER_POSTMARK,
            self::PROVIDER_RESEND,
        ]);
    }

    /**
     * Get SMTP configuration fields.
     */
    public static function getSmtpConfigFields(): array
    {
        return [
            'host' => ['label' => 'Serveur SMTP', 'type' => 'text', 'required' => true],
            'port' => ['label' => 'Port', 'type' => 'number', 'required' => true, 'default' => 587],
            'encryption' => ['label' => 'Chiffrement', 'type' => 'select', 'options' => ['tls' => 'TLS', 'ssl' => 'SSL', '' => 'Aucun'], 'default' => 'tls'],
            'username' => ['label' => 'Nom d\'utilisateur', 'type' => 'text', 'required' => true],
            'password' => ['label' => 'Mot de passe', 'type' => 'password', 'required' => true],
        ];
    }

    /**
     * Get Postmark configuration fields.
     */
    public static function getPostmarkConfigFields(): array
    {
        return [
            'token' => ['label' => 'Server Token', 'type' => 'password', 'required' => true],
        ];
    }

    /**
     * Get Resend configuration fields.
     */
    public static function getResendConfigFields(): array
    {
        return [
            'api_key' => ['label' => 'Clé API', 'type' => 'password', 'required' => true],
        ];
    }

    /**
     * Get Brevo configuration fields.
     */
    public static function getBrevoConfigFields(): array
    {
        return [
            'username' => ['label' => 'Login SMTP (votre email)', 'type' => 'email', 'required' => true],
            'api_key' => ['label' => 'Clé SMTP', 'type' => 'password', 'required' => true, 'help' => 'Disponible dans Brevo → Paramètres → SMTP & API'],
        ];
    }
}
