<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyBillResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'date'       => $this->date,
            'amount'     => (float) $this->amount,
            'status'     => $this->status,
            'customer'   => new CustomerResource($this->whenLoaded('customer')),
            'items'      => DailyBillItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
