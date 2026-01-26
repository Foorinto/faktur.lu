<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'vat_rate' => $this->vat_rate,
            'subtotal_ht' => $this->subtotal_ht,
            'vat_amount' => $this->vat_amount,
            'total_ttc' => $this->total_ttc,
            'sort_order' => $this->sort_order,

            // Formatted values for display
            'formatted_unit_price' => $this->formatted_unit_price,
            'formatted_subtotal' => $this->formatted_subtotal,
            'formatted_vat_amount' => $this->formatted_vat_amount,
            'formatted_total_ttc' => $this->formatted_total_ttc,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
