<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'firm_name'           => $this->firm_name,
            'gst_number'          => $this->gst_number,
            'location'            => $this->location,
            'contact_person'      => $this->contact_person,
            'phone'               => $this->phone,
            'route'               => $this->route,
            'notes'               => $this->notes,
            'outstanding_balance' => (float) $this->outstanding_balance,
            'created_at'          => $this->created_at?->toIso8601String(),
            'updated_at'          => $this->updated_at?->toIso8601String(),
            'deleted_at'          => $this->deleted_at?->toIso8601String(),
        ];
    }
}
