<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MortalityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'batch'      => new BatchResource($this->whenLoaded('batch')),
            'date'       => $this->date,
            'count'      => $this->count,
            'reason'     => $this->reason,
            'notes'      => $this->notes,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
