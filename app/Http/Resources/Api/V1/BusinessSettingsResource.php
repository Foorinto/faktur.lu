<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessSettingsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_name' => $this->company_name,
            'legal_name' => $this->legal_name,
            'address' => $this->address,
            'postal_code' => $this->postal_code,
            'city' => $this->city,
            'country_code' => $this->country_code,
            'vat_number' => $this->vat_number,
            'matricule' => $this->matricule,
            'iban' => $this->iban,
            'bic' => $this->bic,
            'vat_regime' => $this->vat_regime,
            'phone' => $this->phone,
            'email' => $this->email,
            'logo_path' => $this->logo_path,
            'logo_url' => $this->logo_path ? asset('storage/' . $this->logo_path) : null,
            'formatted_address' => $this->formatted_address,
            'is_vat_exempt' => $this->isVatExempt(),
            'is_vat_registered' => $this->isVatRegistered(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
