<?php

namespace App\Http\Resources\Sale;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "user_id" => $this->user_id,
            "user" => [
                "full_name" => trim(($this->user?->name ?? '').' '.($this->user?->surname ?? '')),
            ],
            "client_id" => $this->client_id,
            "client" => [
                "id" => $this->client?->id,
                "full_name" => $this->client?->full_name,
                "n_document" => $this->client?->n_document,
            ],
            "type_client" => $this->type_client,
            "sucursale_id" => $this->sucursale_id,
            "sucursale" => [
                "name" => $this->sucursale?->name,
            ],
            "subtotal" => $this->subtotal,
            "discount" => $this->discount,
            "total" => $this->total,
            "igv" => $this->igv,
            "state_sale" => $this->state_sale,
            "state_payment" => $this->state_payment,
            "state_entrega" => $this->state_entrega,
            "debt" => $this->debt,
            "paid_out" => $this->paid_out,
            "date_validation" => $this->date_validation,
            "date_pay_complete" => $this->date_pay_complete,
            "description" => $this->description,
            "created_at" => $this->created_at?->format("Y-m-d h:i A"),
            "created_at_format" => $this->created_at?->format("Y-m-d"),
            "sale_details" => $this->sale_details?->map(fn($d) => SaleDetailResource::make($d)),
            "payments" => $this->payments?->map(function ($p) {
                return [
                    "id" => $p->id,
                    "method_payment" => $p->method_payment,
                    "banco" => $p->banco,
                    "amount" => $p->amount,
                    "n_transaction" => $p->n_transaction,
                ];
            }),
        ];
    }
}
