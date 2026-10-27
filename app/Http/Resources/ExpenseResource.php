<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'date'        => $this->date,
            'category'    => $this->category,
            'description' => $this->description,
            'amount'      => (float) $this->amount,
            'created_at'  => $this->created_at?->toDateTimeString(),
        ];
    }
}
