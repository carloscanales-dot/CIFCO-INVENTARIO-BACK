<?php

namespace App\Http\Resources\Dispatch;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class DispatchCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->transform(function ($dispatch) {
                return [
                    'id' => $dispatch->id,

                    'warehouse' => $dispatch->warehouse?->name,
                    'requester' => $dispatch->requester?->full_name,

                    'requisition_number' => $dispatch->requisition_number,
                    'reference' => $dispatch->reference,

                    'date_emision' => $dispatch->date_emision?->format('Y/m/d'),
                    'state' => $dispatch->state,

                    'total_items' => $dispatch->details->count(),

                    'created_at' => $dispatch->created_at?->format('Y/m/d H:i'),
                ];
            }),
        ];
    }
}
