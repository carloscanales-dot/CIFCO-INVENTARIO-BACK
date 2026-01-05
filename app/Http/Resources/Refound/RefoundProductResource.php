<?php

namespace App\Http\Resources\Refound;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefoundProductResource extends JsonResource
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
            "product_id"  => $this->resource->product_id,
            "product" => [
                "title" =>$this->resource->product->title,
                "sku" => $this->resource->product->sku,
                "imagen" => $this->resource->product->product_imagen,
            ],
            "unit_id"  => $this->resource->unit_id,
            "unit" => [
                "name" => $this->resource->unit->name,
            ],
            "warehouse_id"  => $this->resource->warehouse_id,
            "warehouse" => [
                "name" => $this->resource->warehouse->name,
            ],
            "quantity"  => $this->resource->quantity,
            "sale_detail_id"  => $this->resource->sale_detail_id,
            "sale_id" => $this->resource->sale_detail->sale_id,
            "client_id"  => $this->resource->client_id,
            "client" => [
                "full_name" => $this->resource->client->full_name,
            ],
            "type"  => $this->resource->type,
            "state"  => $this->resource->state,
            "description"  => $this->resource->description,
            "user_id"  => $this->resource->user_id,
            "resolution_date"  => $this->resource->resolution_date,
            "description_resolution"  => $this->resource->description_resolution,
            "created_at" => $this->resource->created_at->format("Y/m/d h:i A"),
        ];
    }
}
