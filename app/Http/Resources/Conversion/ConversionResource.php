<?php

namespace App\Http\Resources\Conversion;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversionResource extends JsonResource
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
            "product_id" => $this->resource->product_id,
            "product" => [
                "title" => $this->resource->product->title,
                "sku" => $this->resource->product->sku,
                "imagen" => $this->resource->product->product_imagen
            ],
            "warehouse_id" => $this->resource->warehouse_id,
            "warehouse" => [
                "name" => $this->resource->warehouse->name,
            ],
            "unit_start_id" => $this->resource->unit_start_id,
            "unit_start" => [
                "name" => $this->resource->unit_start->name,
            ],
            "unit_end_id" => $this->resource->unit_end_id,
            "unit_end" => [
                "name" => $this->resource->unit_end->name,
            ],
            "user_id" => $this->resource->user_id,
            "user" => [
                "full_name" => $this->resource->user->name.' '.$this->resource->user->surname,
            ],
            "quantity_start" => $this->resource->quantity_start,
            "quantity_end" => $this->resource->quantity_end,
            "description" => $this->resource->description,
            "created_at" => $this->resource->created_at->format("Y/m/d h:i A")
        ];
    }
}
