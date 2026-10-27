<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WeeklyBillResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'invoice_number' => $this->invoice_number,
            'period_start'   => $this->period_start,
            'period_end'     => $this->period_end,
            'amount'         => (float) $this->amount,
            'status'         => $this->status,
            'customer'       => new CustomerResource($this->whenLoaded('customer')),
            'items'          => WeeklyBillItemResource::collection($this->whenLoaded('items')),
            'created_at'     => $this->created_at?->toDateTimeString(),
        ];
    }
}
