<?php

namespace App\Http\Resources\Dispatch;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DispatchDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'product' => $this->product ? [
                'id' => $this->product->id,
                'title' => $this->product->title,
                'sku' => $this->product->sku,
            ] : null,

            'warehouse' => $this->warehouse ? [
                'id' => $this->warehouse->id,
                'name' => $this->warehouse->name,
            ] : null,

            'unit' => $this->unit ? [
                'id' => $this->unit->id,
                'name' => $this->unit->name,
            ] : null,

            'quantity' => $this->quantity,
        ];
    }
}
