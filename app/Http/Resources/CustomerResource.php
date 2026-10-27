<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'phone'              => $this->phone,
            'address'            => $this->address,
            'gst_number'         => $this->gst_number,
            'route'              => $this->route,
            'route_id'           => $this->route_id,
            'type'               => $this->type,
            'balance'            => (float) $this->balance,
            'formatted_balance'  => $this->formatted_balance,
            'created_at'         => $this->created_at?->toIso8601String(),
            'updated_at'         => $this->updated_at?->toIso8601String(),
            'deleted_at'         => $this->deleted_at?->toIso8601String(),
        ];
    }
}
