<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            "title" => $this->resource->title,
            "sku" => $this->resource->sku,
            "imagen" => $this->resource->product_imagen,
            "product_categorie_id" => $this->resource->product_categorie_id,
            /*"product_categorie" => [
                "id" => $this->resource->product_categorie->id,
                "name" => $this->resource->product_categorie->title,
            ],*/
            "product_categorie" => [
                "id" => $this->resource->product_categorie?->id,
                "name" => $this->resource->product_categorie?->title,
            ],
            "price_general" => $this->resource->price_general,
            "price_company" => $this->resource->price_company,
            "description" => $this->resource->description,
            "is_discount" => $this->resource->is_discount,
            "max_discount" => $this->resource->max_discount,
            "is_gift" => $this->resource->is_gift,
            "disponibilidad" => $this->resource->disponibilidad,
            "state" => $this->resource->state,
            "state_stock" => $this->resource->state_stock,
            "warranty_day" => $this->resource->warranty_day,
            "tax_selected" => $this->resource->tax_selected,
            "importe_iva" => $this->resource->importe_iva,
            'expiration_date' => $this->resource->expiration_date,
            "created_at" => $this->resource->created_at->format("Y/m/d h:i A"),
            "warehouses" => $this->resource->warehouses->map(function($warehouse) {
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
            "wallets" => $this->resource->wallets->sortByDesc("id")->map(function($wallet) {
                return [
                    "id" => $wallet->id,
                    "type_client" => $wallet->type_client,
                    "type_client_name" => $wallet->type_client == 1 ? 'Cliente Final' : 'Cliente Empresa',
                    "sucursale_id" => $wallet->sucursale_id,
                    "sucursale" => $wallet->sucursale ? [
                        "name" => $wallet->sucursale->name,
                    ] : NULL,
                    "unit_id" => $wallet->unit_id,
                    "unit" => [
                        "name" => $wallet->unit->name,
                    ],
                    "price" => $wallet->price,
                ];
            })->values()->all()
        ];
    }
}
