<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date->format('Y-m-d'),
            'provider_name' => $this->provider_name,
            'category' => $this->category,
            'category_label' => $this->category_label,
            'amount_ht' => $this->amount_ht,
            'vat_rate' => $this->vat_rate,
            'amount_vat' => $this->amount_vat,
            'amount_ttc' => $this->amount_ttc,
            'description' => $this->description,
            'is_deductible' => $this->is_deductible,
            'payment_method' => $this->payment_method,
            'payment_method_label' => $this->payment_method_label,
            'reference' => $this->reference,
            'attachment_url' => $this->attachment_url,
            'attachment_filename' => $this->attachment_filename,
            'has_attachment' => $this->hasAttachment(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
