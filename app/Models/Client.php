<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'contact_name',
        'email',
        'address',
        'postal_code',
        'city',
        'country_code',
        'vat_number',
        'registration_number',
        'type',
        'currency',
        'phone',
        'notes',
        'default_hourly_rate',
    ];

    protected $casts = [
        'type' => 'string',
        'default_hourly_rate' => 'decimal:2',
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
     * Generate a snapshot of the client data for invoice immutability.
     */
    public function toSnapshot(): array
    {
        return [
            'name' => $this->name,
            'contact_name' => $this->contact_name,
            'email' => $this->email,
            'address' => $this->address,
            'postal_code' => $this->postal_code,
            'city' => $this->city,
            'country_code' => $this->country_code,
            'vat_number' => $this->vat_number,
            'registration_number' => $this->registration_number,
            'type' => $this->type,
            'phone' => $this->phone,
        ];
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
        $euCountries = [
            'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR',
            'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL',
            'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE',
        ];

        return in_array($this->country_code, $euCountries);
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
