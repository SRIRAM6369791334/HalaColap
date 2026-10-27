<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'invoice_no'      => $this->invoice_no,
            'date'            => $this->date,
            'vendor_name'     => $this->vendor_name,
            'vendor'          => new VendorResource($this->whenLoaded('vendor')),
            'gst_percentage'  => (float) $this->gst_percentage,
            'gst_amount'      => (float) $this->gst_amount,
            'total_amount'    => (float) $this->total_amount,
            'payment_status'  => $this->payment_status,
            'payment_mode'    => $this->payment_mode,
            'credit_days'     => $this->credit_days,
            'due_date'        => $this->due_date,
            'notes'           => $this->notes,
            'items'           => PurchaseItemResource::collection($this->whenLoaded('items')),
            'created_at'      => $this->created_at?->toDateTimeString(),
        ];
    }
}
