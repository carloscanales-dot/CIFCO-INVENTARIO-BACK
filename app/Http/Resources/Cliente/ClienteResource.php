<?php

namespace App\Http\Resources\Cliente;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClienteResource extends JsonResource
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
            "name"  => $this->resource->name,
            "surname"  => $this->resource->surname,
            "full_name"  => $this->resource->full_name,
            "phone"  => $this->resource->phone,
            "email"  => $this->resource->email,
            "type_client"  => $this->resource->type_client,
            "type_document"  => $this->resource->type_document,
            "n_document"  => $this->resource->n_document,
            "birth_date"  => Carbon::parse($this->resource->birth_date)->format("Y-m-d"),
            "user_id"  => $this->resource->user_id,
            "user" => [
                "full_name" => $this->resource->user->name.' '.$this->resource->user->surname,
            ],
            "sucursale_id"  => $this->resource->sucursale_id,
            "sucursale" => [
                "name" => $this->resource->sucursale->name,
            ],
            "state"  => $this->resource->state,
            "gender"  => $this->resource->gender,
            /*"ubigeo_region"  => $this->resource->ubigeo_region,
            "ubigeo_provincia"  => $this->resource->ubigeo_provincia,
            "ubigeo_distrito"  => $this->resource->ubigeo_distrito,
            "region"  => $this->resource->region,
            "provincia"  => $this->resource->provincia,
            "distrito"  => $this->resource->distrito,*/
            "ubigeo_region"  => $this->resource->ubigeo_region ?? null,
            "ubigeo_provincia"  => $this->resource->ubigeo_provincia ?? null,
            "ubigeo_distrito"  => $this->resource->ubigeo_distrito ?? null,
            "region"  => $this->resource->region ?? null,
            "provincia"  => $this->resource->provincia ?? null,
            "distrito"  => $this->resource->distrito ?? null,
            "address" => $this->resource->address,
            "created_at" => $this->resource->created_at->format("Y-m-d h:i A")
        ];
    }
}
