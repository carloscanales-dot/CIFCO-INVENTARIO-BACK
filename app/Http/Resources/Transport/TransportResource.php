<?php

namespace App\Http\Resources\Transport;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->resource->id,
            "warehouse_start_id" => $this->resource->warehouse_start_id,
            "warehouse_end_id" => $this->resource->warehouse_end_id,
            "warehouse_start" => [
                "name" => $this->resource->warehouse_start->name,
            ],
            "warehouse_end" => [
                "name" => $this->resource->warehouse_end->name,
            ],
            "user_id" => $this->resource->user_id,
            "user" => [
                "full_name" => $this->resource->user->name.' '.$this->resource->user->surname,
            ],
            "date_emision" => Carbon::parse($this->resource->date_emision)->format("Y-m-d"),
            "state" => $this->resource->state,
            "total" => $this->resource->total,
            "importe" => $this->resource->importe,
            "igv" => $this->resource->igv,
            "description" => $this->resource->description,
            "created_at" => $this->resource->created_at->format("Y-m-d h:i A"),
            "details" => $this->resource->transport_details->map(function($transport_detail) {
                return [
                    "id" => $transport_detail->id,
                    "product_id"  => $transport_detail->product_id,
                    "product" => [
                        "title" => $transport_detail->product->title,
                        "sku" => $transport_detail->product->sku,
                        "warehouses" => $transport_detail->product->warehouses->map(function($warehouse) {
                            return [
                                "id" => $warehouse->id,
                                "warehouse_id" => $warehouse->warehouse_id,
                                "warehouse" => [
                                    "name" => $warehouse->warehouse->name,
                                ],
                                "unit_id" => $warehouse->unit_id,
                                "unit" => [
                                    "name" => $warehouse->unit->name,
                                ],
                                "stock" => $warehouse->stock,
                                "umbral" => $warehouse->umbral,
                                "state_stock" => $warehouse->state_stock,
                            ];
                        }),
                    ],
                    "unit_id"  => $transport_detail->unit_id,
                    "unit" => [
                        "name" => $transport_detail->unit->name,
                    ],
                    "price_unit"  => $transport_detail->price_unit,
                    "total"  => $transport_detail->total,
                    "quantity"  => $transport_detail->quantity,
                    "state"  => $transport_detail->state,
                    "user_entrega"  => $transport_detail->user_entrega ? [
                        "id" => $transport_detail->user_in->id,
                        "full_name" => $transport_detail->user_in->name.' '.$transport_detail->user_in->surname,
                    ]: NULL,
                    "date_entrega"  => $transport_detail->date_entrega ? Carbon::parse($transport_detail->date_entrega)->format("Y-m-d") : null,
                    "user_salida"  => $transport_detail->user_salida ? [
                        "id" => $transport_detail->user_out->id,
                        "full_name" => $transport_detail->user_out->name.' '.$transport_detail->user_out->surname,
                    ]: NULL,
                    "date_salida"  => $transport_detail->date_salida ? Carbon::parse($transport_detail->date_salida)->format("Y-m-d") : null,
                    "description" => $transport_detail->description,
                ];
            })
        ];
    }
}
