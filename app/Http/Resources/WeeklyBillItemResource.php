<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WeeklyBillItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'item_name'   => $this->item_name,
            'quantity_kg' => (float) $this->quantity_kg,
            'rate'        => (float) $this->rate,
            'amount'      => (float) $this->amount,
        ];
    }
}
