<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'txn_type'       => $this->txn_type,
            'item_name'      => $this->item_name,
            'quantity'       => (float) $this->quantity,
            'unit'           => $this->unit,
            'date'           => $this->date,
            'notes'          => $this->notes,
            'reference_type' => class_basename($this->reference_type ?? ''),
            'reference_id'   => $this->reference_id,
            'created_at'     => $this->created_at?->toDateTimeString(),
        ];
    }
}
