<?php

namespace App\Models;

use App\Services\VatCalculationService;
use App\Traits\Auditable;
use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes, BelongsToUser, Auditable;

    protected $fillable = [
        'name',
        'contact_name',
        'email',
        'address',
        'postal_code',
        'city',
        'country_code',
        'vat_number',
        'peppol_endpoint_id',
        'peppol_endpoint_scheme',
        'registration_number',
        'type',
        'currency',
        'phone',
        'notes',
        'default_hourly_rate',
        'default_vat_rate',
        'locale',
        'exclude_from_reminders',
    ];

    protected $casts = [
        'type' => 'string',
        'default_hourly_rate' => 'decimal:2',
        'default_vat_rate' => 'decimal:2',
        'exclude_from_reminders' => 'boolean',
    ];

    /**
     * Get the invoices for the client.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the time entries for the client.
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    /**
     * Get the projects for the client.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Generate a snapshot of the client data for invoice immutability.
     */
    public function toSnapshot(): array
    {
        $vatScenario = $this->vat_scenario;

        return [
            'name' => $this->name,
            'contact_name' => $this->contact_name,
            'email' => $this->email,
            'address' => $this->address,
            'postal_code' => $this->postal_code,
            'city' => $this->city,
            'country_code' => $this->country_code,
            'vat_number' => $this->vat_number,
            'peppol_endpoint_id' => $this->peppol_endpoint_id,
            'peppol_endpoint_scheme' => $this->peppol_endpoint_scheme,
            'registration_number' => $this->registration_number,
            'type' => $this->type,
            'phone' => $this->phone,
            'locale' => $this->locale ?? 'fr',
            'vat_scenario' => $vatScenario['key'],
            'suggested_vat_rate' => $vatScenario['rate'],
            'suggested_vat_mention' => $vatScenario['mention'],
        ];
    }

    /**
     * Get the full Peppol endpoint identifier (scheme:id format).
     */
    public function getPeppolEndpointAttribute(): ?string
    {
        if ($this->peppol_endpoint_id && $this->peppol_endpoint_scheme) {
            return "{$this->peppol_endpoint_scheme}:{$this->peppol_endpoint_id}";
        }

        return null;
    }

    /**
     * Check if the client has a Peppol endpoint configured.
     */
    public function hasPeppolEndpoint(): bool
    {
        return !empty($this->peppol_endpoint_id) && !empty($this->peppol_endpoint_scheme);
    }

    /**
     * Get the formatted address for display.
     */
    public function getFormattedAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->postal_code && $this->city ? "{$this->postal_code} {$this->city}" : null,
            $this->country_code !== 'LU' ? $this->country_code : null,
        ]);

        return implode("\n", $parts);
    }

    /**
     * Check if the client is a business (B2B).
     */
    public function isBusiness(): bool
    {
        return $this->type === 'b2b';
    }

    /**
     * Check if the client is an individual (B2C).
     */
    public function isIndividual(): bool
    {
        return $this->type === 'b2c';
    }

    /**
     * Check if the client is from an EU country.
     */
    public function isEuClient(): bool
    {
        return in_array($this->country_code, VatCalculationService::EU_COUNTRIES);
    }

    /**
     * Check if the client is from an EU country other than Luxembourg (intra-EU).
     */
    public function isIntraEuClient(): bool
    {
        return $this->country_code !== 'LU' && $this->isEuClient();
    }

    /**
     * Check if the client is from outside the EU.
     */
    public function isNonEuClient(): bool
    {
        return !$this->isEuClient();
    }

    /**
     * Check if the client is from Luxembourg.
     */
    public function isLuxembourgClient(): bool
    {
        return $this->country_code === 'LU';
    }

    /**
     * Get the VAT scenario for this client.
     */
    public function getVatScenarioAttribute(): array
    {
        $service = app(VatCalculationService::class);
        return $service->determineScenario($this);
    }

    /**
     * Get the suggested VAT rate for this client.
     */
    public function getSuggestedVatRateAttribute(): float
    {
        return (float) $this->vat_scenario['rate'];
    }

    /**
     * Get the suggested VAT mention for this client.
     */
    public function getSuggestedVatMentionAttribute(): ?string
    {
        return $this->vat_scenario['mention'];
    }

    /**
     * Check if this client qualifies for reverse charge (autoliquidation).
     */
    public function qualifiesForReverseCharge(): bool
    {
        return $this->isIntraEuClient()
            && $this->type === 'b2b'
            && !empty($this->vat_number);
    }

    /**
     * Check if VAT should be exempted for this client.
     */
    public function isVatExempt(): bool
    {
        $scenario = $this->vat_scenario;
        return $scenario['rate'] === 0;
    }

    /**
     * Check if the client can be deleted (no linked invoices).
     */
    public function canBeDeleted(): bool
    {
        return $this->invoices()->count() === 0;
    }

    /**
     * Scope to search clients by name or email.
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('vat_number', 'like', "%{$search}%");
        });
    }

    /**
     * Scope to filter by type.
     */
    public function scopeOfType(Builder $query, ?string $type): Builder
    {
        if (empty($type)) {
            return $query;
        }

        return $query->where('type', $type);
    }

    /**
     * Scope to filter by country.
     */
    public function scopeFromCountry(Builder $query, ?string $countryCode): Builder
    {
        if (empty($countryCode)) {
            return $query;
        }

        return $query->where('country_code', $countryCode);
    }
}
