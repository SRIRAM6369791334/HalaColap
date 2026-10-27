<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'batch_name'    => $this->batch_name,
            'breed'         => $this->breed,
            'start_date'    => $this->start_date,
            'end_date'      => $this->end_date,
            'initial_count' => $this->initial_count,
            'current_count' => $this->current_count,
            'status'        => $this->status,
            'notes'         => $this->notes,
            'created_at'    => $this->created_at?->toDateTimeString(),
        ];
    }
}
