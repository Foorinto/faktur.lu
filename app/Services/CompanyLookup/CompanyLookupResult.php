<?php

namespace App\Services\CompanyLookup;

class CompanyLookupResult
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $address = null,
        public readonly ?string $postalCode = null,
        public readonly ?string $city = null,
        public readonly string $countryCode = '',
        public readonly ?string $vatNumber = null,
        public readonly ?string $registrationNumber = null,
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'address' => $this->address,
            'postal_code' => $this->postalCode,
            'city' => $this->city,
            'country_code' => $this->countryCode,
            'vat_number' => $this->vatNumber,
            'registration_number' => $this->registrationNumber,
        ];
    }
}
