<?php

namespace App\Models\Purchase;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Config\Provider;
use App\Models\Config\Sucursale;
use App\Models\Config\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use SoftDeletes;
    protected $fillable = [
        "warehouse_id",
        "user_id",
        "sucursale_id",
        "date_emision",
        "state",
        "type_comprobant",
        "n_comprobant",
        "provider_id",
        "total",
        "importe",
        "igv",
        "description"
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

    public function purchase_details(){
        return $this->hasMany(PurchaseDetail::class,"purchase_id");
    }

    public function warehouse() {
        return $this->belongsTo(Warehouse::class,"warehouse_id");
    }
    public function user() {
        return $this->belongsTo(User::class,"user_id");
    }
    public function sucursale() {
        return $this->belongsTo(Sucursale::class,"sucursale_id");
    }
    public function provider() {
        return $this->belongsTo(Provider::class,"provider_id");
    }

    public function getDateEmisionFormatAttribute(){
        return Carbon::parse($this->date_emision)->format("Y/m/d");
    }

    public function scopeFilterAdvance($query,$search,$warehouse_id,$unit_id,$provider_id,$type_comprobant,$start_date,$end_date,$search_product,$user){

        if($search){
            $query->where("id",$search);
        }

        if($warehouse_id){
            $query->where("warehouse_id",$warehouse_id);
        }

        if($provider_id){
            $query->where("provider_id",$provider_id);
        }
        
        if($type_comprobant){
            $query->where("type_comprobant",$type_comprobant);
        }

        if($start_date && $end_date){
            $query->whereBetween("date_emision",[
               Carbon::parse($start_date)->format("Y-m-d")." 00:00:00",
               Carbon::parse($end_date)->format("Y-m-d")." 23:59:59"
            ]);
        }

        if($unit_id){
            $query->whereHas("purchase_details",function($q) use($unit_id){
                $q->where("unit_id",$unit_id);
            });
        }

        if($search_product){
            $query->whereHas("purchase_details",function($q) use($search_product){
                $q->whereHas("product",function($subq) use($search_product){
                    $subq->where(DB::raw("products.title || '' || products.sku"),"ilike","%".$search_product."%");
                });
            });
        }
        if($user){
            if($user->role_id != 1){
                if($user->role_id == 2){
                    $query->where("sucursale_id",$user->sucursale_id);
                }else{
                    $query->where("user_id",$user->id);
                }
            }
        }
        return $query;
    }
}
