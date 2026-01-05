<?php

namespace App\Models\Product;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Config\ProductCategorie;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;
    protected $fillable = [
        "title",
        "sku",
        "imagen",
        "product_categorie_id",
        "price_general",
        "price_company",
        "description",
        "is_discount",
        "max_discount",
        "is_gift",
        "disponibilidad",
        "state",
        "state_stock",
        "warranty_day",
        "tax_selected",
        "importe_iva",
        'expiration_date',
    ];

    public function setCreatedAtAttribute($value)
    {
    	date_default_timezone_set('America/Lima');
        $this->attributes["created_at"]= Carbon::now();
    }

    public function setUpdatedAtAttribute($value)
    {
    	date_default_timezone_set("America/Lima");
        $this->attributes["updated_at"]= Carbon::now();
    }

    public function product_categorie() {
        return $this->belongsTo(ProductCategorie::class,"product_categorie_id");
    }

    // LAS EXISTENCIAS DISPONIBLES DE UN PRODUCTO
    public function warehouses() {
        return $this->hasMany(ProductWarehouse::class);
    }

    // LOS PRECIOS DE UN PRODUCTO
    public function wallets() {
        return $this->hasMany(ProductWallet::class);
    }

    public function getProductImagenAttribute()
    {
        $link = null;
        if($this->imagen){
            if(str_contains($this->imagen,"https://") || str_contains($this->imagen,"http://")){
                $link = $this->imagen;
            }else{
                $link =  asset('storage/'.$this->imagen);
            }
        }
        return $link;
    }

    public function scopeFilterAdvance($query,$search,$categorie_id,$warehouse_id,$unit_id,$sucursale_id,$disponibilidad,$is_gift){
        if($search){
            $query->where(DB::raw("products.title || '' || products.sku"),"ilike","%".$search."%");
        }
        if($categorie_id){
            $query->where("product_categorie_id",$categorie_id);
        }
        if($disponibilidad){
            $query->where("disponibilidad",$disponibilidad);
        }
        if($is_gift){
            $query->where("is_gift",$is_gift);
        }
        if($warehouse_id){
            $query->whereHas("warehouses",function($warehouse) use($warehouse_id){
                $warehouse->where("warehouse_id",$warehouse_id);
            });
        }
        if($unit_id){
            $query->whereHas("warehouses",function($warehouse) use($unit_id){
                $warehouse->where("unit_id",$unit_id);
            });
        }
        if($sucursale_id){
            $query->whereHas("wallets",function($wallet) use($sucursale_id){
                $wallet->where("sucursale_id",$sucursale_id);
            });
        }
        return $query;
    }
}
