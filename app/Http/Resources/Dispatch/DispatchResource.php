<?php

namespace App\Http\Resources\Dispatch;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DispatchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'warehouse_id' => $this->warehouse_id,
            'warehouse' => [
                'name' => $this->warehouse?->name ?? 'Sin almacén',
            ],

            'requester_id' => $this->requester_id,
            'requester' => [
                'full_name' => $this->requester?->full_name ?? 'Sin solicitante',
            ],

            'user_id' => $this->user_id,
            'user' => [
                'full_name' => trim(
                    ($this->user?->name ?? '') . ' ' . ($this->user?->surname ?? '')
                ),
            ],

            'sucursale_id' => $this->sucursale_id,

            'requisition_number' => $this->requisition_number,
            'area_id' => $this->area_id,
            'reference' => $this->reference,

            'date_emision' => $this->date_emision
                ? Carbon::parse($this->date_emision)->format('Y-m-d')
                : null,

            'description' => $this->description,
            'state' => $this->state,

            'created_at' => $this->created_at
                ? $this->created_at->format('Y-m-d h:i A')
                : null,

            'details' => $this->details->map(function ($detail) {
                return [
                    'id' => $detail->id,

                    'product_id' => $detail->product_id,
                    'product' => [
                        'title' => $detail->product?->title ?? 'Producto eliminado',
                        'sku'   => $detail->product?->sku ?? 'N/A',
                    ],

                    'warehouse_id' => $detail->warehouse_id,
                    'warehouse' => [
                        'name' => $detail->warehouse?->name ?? 'Sin almacén',
                    ],

                    'unit_id' => $detail->unit_id,
                    'unit' => [
                        'name' => $detail->unit?->name ?? 'Sin unidad',
                    ],

                    'quantity' => $detail->quantity,

                    'created_at' => $detail->created_at
                        ? $detail->created_at->format('Y-m-d h:i A')
                        : null,
                ];
            }),
        ];
    }
}
