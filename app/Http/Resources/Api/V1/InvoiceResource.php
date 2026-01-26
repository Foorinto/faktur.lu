<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'display_number' => $this->display_number,
            'status' => $this->status,
            'type' => $this->type,
            'currency' => $this->currency,

            // Client info
            'client_id' => $this->client_id,
            'client' => new ClientResource($this->whenLoaded('client')),

            // Snapshots (for finalized invoices)
            'seller' => $this->seller,
            'buyer' => $this->buyer,
            'seller_snapshot' => $this->when($this->seller_snapshot, $this->seller_snapshot),
            'buyer_snapshot' => $this->when($this->buyer_snapshot, $this->buyer_snapshot),

            // Amounts
            'total_ht' => $this->total_ht,
            'total_vat' => $this->total_vat,
            'total_ttc' => $this->total_ttc,
            'vat_breakdown' => $this->vat_breakdown,

            // Dates
            'issued_at' => $this->issued_at?->format('Y-m-d'),
            'due_at' => $this->due_at?->format('Y-m-d'),
            'finalized_at' => $this->finalized_at?->toISOString(),
            'sent_at' => $this->sent_at?->toISOString(),
            'paid_at' => $this->paid_at?->toISOString(),

            // Metadata
            'notes' => $this->notes,
            'payment_reference' => $this->payment_reference,

            // Credit note relation
            'credit_note_for' => $this->credit_note_for,
            'original_invoice' => new InvoiceResource($this->whenLoaded('originalInvoice')),
            'credit_note' => new InvoiceResource($this->whenLoaded('creditNote')),

            // Items
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->whenCounted('items'),

            // Computed properties
            'is_draft' => $this->isDraft(),
            'is_finalized' => $this->isFinalized(),
            'is_immutable' => $this->isImmutable(),
            'is_paid' => $this->isPaid(),
            'is_credit_note' => $this->isCreditNote(),
            'can_create_credit_note' => $this->canCreateCreditNote(),
            'is_seller_vat_exempt' => $this->isSellerVatExempt(),

            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
