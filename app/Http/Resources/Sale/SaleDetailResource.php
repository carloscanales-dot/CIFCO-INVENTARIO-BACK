<?php

namespace App\Http\Resources\Sale;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "product_id" => $this->product_id,
            "product" => [
                "id" => $this->product?->id,
                "title" => $this->product?->title,
                "sku" => $this->product?->sku,
                "warehouses" => $this->product?->warehouses?->map(function ($warehouse) {
                    return [
                        "id" => $warehouse->id,
                        "warehouse_id" => $warehouse->warehouse_id,
                        "warehouse" => [
                            "name" => $warehouse->warehouse?->name,
                        ],
                        "unit_id" => $warehouse->unit_id,
                        "unit" => [
                            "name" => $warehouse->unit?->name,
                        ],
                        "stock" => $warehouse->stock,
                        "umbral" => $warehouse->umbral,
                        "state_stock" => $warehouse->state_stock,
                    ];
                }),
                "wallets" => $this->product?->wallets?->map(function ($wallet) {
                    return [
                        "id" => $wallet->id,
                        "type_client" => $wallet->type_client,
                        "type_client_name" => $wallet->type_client == 1 ? 'Cliente Final' : 'Cliente Empresa',
                        "sucursale_id" => $wallet->sucursale_id,
                        "sucursale" => $wallet->sucursale ? [
                            "name" => $wallet->sucursale?->name,
                        ] : null,
                        "unit_id" => $wallet->unit_id,
                        "unit" => [
                            "name" => $wallet->unit?->name,
                        ],
                        "price" => $wallet->price,
                    ];
                }),
                "tax_selected" => $this->product?->tax_selected,
                "importe_iva" => $this->product?->importe_iva,
                "price_general" => $this->product?->price_general,
                "price_company" => $this->product?->price_company,
                "is_discount" => $this->product?->is_discount,
                "max_discount" => $this->product?->max_discount,
                "disponibilidad" => $this->product?->disponibilidad,
                "is_gift" => $this->product?->is_gift,
                "imagen" => $this->product?->product_imagen,
            ],
            "product_categorie_id" => $this->product_categorie_id,
            "product_categorie" => [
                "title" => $this->product_categorie?->title,
            ],
            "unit_id" => $this->unit_id,
            "unit" => [
                "id" => $this->unit?->id,
                "name" => $this->unit?->name,
            ],
            "warehouse_id" => $this->warehouse_id,
            "warehouse" => [
                "id" => $this->warehouse?->id,
                "name" => $this->warehouse?->name,
            ],
            "quantity" => $this->quantity,
            "price" => $this->price_unit,
            "discount" => $this->discount,
            "subtotal" => $this->subtotal,
            "igv" => $this->igv,
            "total" => $this->total,
            "description" => $this->description,
            "state_attention" => $this->state_attention ?? 1,
            "quantity_pending" => $this->quantity_pending,
        ];
    }
}
