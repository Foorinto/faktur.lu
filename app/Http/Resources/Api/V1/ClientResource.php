<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'address' => $this->address,
            'postal_code' => $this->postal_code,
            'city' => $this->city,
            'country_code' => $this->country_code,
            'vat_number' => $this->vat_number,
            'type' => $this->type,
            'type_label' => $this->type === 'b2b' ? 'Entreprise' : 'Particulier',
            'currency' => $this->currency,
            'phone' => $this->phone,
            'notes' => $this->notes,
            'formatted_address' => $this->formatted_address,
            'is_business' => $this->isBusiness(),
            'is_eu_client' => $this->isEuClient(),
            'can_be_deleted' => $this->canBeDeleted(),
            'invoices_count' => $this->whenCounted('invoices'),
            'time_entries_count' => $this->whenCounted('timeEntries'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
