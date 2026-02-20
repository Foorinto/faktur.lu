<?php

namespace App\Services\CompanyLookup;

class ViesValidationResult
{
    public function __construct(
        public readonly bool $valid,
        public readonly ?string $name = null,
        public readonly ?string $address = null,
        public readonly ?string $countryCode = null,
        public readonly ?string $vatNumber = null,
        public readonly ?string $requestDate = null,
    ) {}

    public static function invalid(): self
    {
        return new self(valid: false);
    }

    public function toArray(): array
    {
        return [
            'valid' => $this->valid,
            'name' => $this->name,
            'address' => $this->address,
            'country_code' => $this->countryCode,
            'vat_number' => $this->vatNumber,
        ];
    }
}
