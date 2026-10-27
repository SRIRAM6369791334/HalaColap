<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'customer'    => new CustomerResource($this->whenLoaded('customer')),
            'amount'      => (float) $this->amount,
            'date'        => $this->date,
            'mode'        => $this->mode,
            'reference'   => $this->reference,
            'notes'       => $this->notes,
            'created_at'  => $this->created_at?->toDateTimeString(),
        ];
    }
}
