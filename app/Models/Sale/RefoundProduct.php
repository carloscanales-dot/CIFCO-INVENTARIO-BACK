<?php

namespace App\Models\Sale;

use Carbon\Carbon;
use App\Models\Config\Unit;
use App\Models\Client\Client;
use App\Models\Product\Product;
use App\Models\Config\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RefoundProduct extends Model
{
    use SoftDeletes;
    protected $fillable = [
        "product_id",
        "unit_id",
        "warehouse_id",
        "quantity",
        "sale_detail_id",
        "client_id",
        "type",
        "state",
        "state_clone",
        "description",
        "user_id",
        "resolution_date",
        "description_resolution"
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

    public function product(){
        return $this->belongsTo(Product::class,"product_id");
    }
    public function unit(){
        return $this->belongsTo(Unit::class,"unit_id");
    }
    public function warehouse(){
        return $this->belongsTo(Warehouse::class,"warehouse_id");
    }
    public function sale_detail(){
        return $this->belongsTo(SaleDetail::class,"sale_detail_id");
    }
    public function client(){
        return $this->belongsTo(Client::class,"client_id");
    }

    public function scopeFilterAdvance($query,$search_product,$warehouse_id,$unit_id,$type,$state,$sale_id,$search_client,$start_date,$end_date,$user){

        if($search_product){
            $query->whereHas("product",function($q) use($search_product){
                $q->where(DB::raw("products.title || '' || products.sku"),"ilike","%".$search_product."%");
            });
        }
        if($warehouse_id){
            $query->where("warehouse_id",$warehouse_id);
        }
        if($unit_id){
            $query->where("unit_id",$unit_id);
        }
        if($type){
            $query->where("type",$type);
        }
        if($state){
            $query->where("state",$state);
        }
        if($sale_id){
            $query->whereHas("sale_detail",function($q) use($sale_id){
                $q->where("sale_id",$sale_id);
            });
        }
        if($search_client){
            $query->whereHas("client",function($q) use($search_client){
                $q->where(DB::raw("clients.full_name || '' || clients.n_document || '' || clients.phone || '' || COALESCE(clients.email,'')"),"ilike","%".$search_client."%");
            });
        }
        if($start_date && $end_date){
            $query->whereBetween("created_at",[Carbon::parse($start_date)->format("Y-m-d")." 00:00:00", Carbon::parse($end_date)->format("Y-m-d")." 23:59:59"]);
        }
        if($user){
            $query->whereHas("sale_detail",function($q) use($user){
                $q->whereHas("sale",function($subq) use($user){
                    if($user->role_id != 1){
                        if($user->role_id == 2){
                            $subq->where("sucursale_id",$user->sucursale_id);
                        }else{
                            $subq->where("user_id",$user->id);
                        }
                    }
                });
            });
        }
        return $query;
    }
}
