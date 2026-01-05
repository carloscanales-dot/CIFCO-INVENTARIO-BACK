<?php

namespace App\Models\Product;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Config\Unit;
use App\Models\Config\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Conversion extends Model
{
    use SoftDeletes;
    protected $fillable = [
        "product_id",
        "warehouse_id",
        "unit_start_id",
        "unit_end_id",
        "user_id",
        "quantity_start",
        "quantity_end",
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

    public function product() {
        return $this->belongsTo(Product::class,"product_id");
    }

    public function warehouse() {
        return $this->belongsTo(Warehouse::class,"warehouse_id");
    }
    
    public function unit_start() {
        return $this->belongsTo(Unit::class,"unit_start_id");
    }

    public function unit_end() {
        return $this->belongsTo(Unit::class,"unit_end_id");
    }

    public function user() {
        return $this->belongsTo(User::class,"user_id");
    }

    public function scopeFilterAdvance($query,$search_product,$warehouse_id,$unit_start_id,$unit_end_id,$start_date,$end_date,$conversion_id) {
        if($search_product){
            $query->whereHas("product",function($q) use($search_product){
                $q->where(DB::raw("products.title || '' || products.sku"),"ilike","%".$search_product."%");
            });
        }
        if($warehouse_id){
            $query->where("warehouse_id",$warehouse_id);
        }
        if($unit_start_id){
            $query->where("unit_start_id",$unit_start_id);
        }
        if($unit_end_id){
            $query->where("unit_end_id",$unit_end_id);
        }
        if($start_date && $end_date){
            $query->whereBetween("created_at",[
                Carbon::parse($start_date)->format("Y-m-d")." 00:00:00",
                Carbon::parse($end_date)->format("Y-m-d")." 23:59:59"
            ]);
        }
        if($conversion_id){
            $query->where("id",$conversion_id);
        }
        return $query;
    }
}
