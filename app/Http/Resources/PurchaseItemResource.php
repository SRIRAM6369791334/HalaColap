<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'item_name'    => $this->item_name,
            'quantity'     => (float) $this->quantity,
            'unit'         => $this->unit,
            'rate'         => (float) $this->rate,
            'tax_amount'   => (float) $this->tax_amount,
            'total_amount' => (float) $this->total_amount,
        ];
    }
}
