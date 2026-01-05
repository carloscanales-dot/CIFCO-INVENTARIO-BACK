<?php

namespace App\Models\Kardex;

use Carbon\Carbon;
use App\Models\Config\Unit;
use App\Models\Product\Product;
use App\Models\Config\Warehouse;
use Illuminate\Database\Eloquent\Model;

class ProductStockInitial extends Model
{
    protected $fillable = [
        "product_id",
        "unit_id",
        "warehouse_id",
        "stock",
        "price_unit_avg",
        "created_at"
    ];

    // public function setCreatedAtAttribute($value)
    // {
    // 	date_default_timezone_set('America/Lima');
    //     $this->attributes["created_at"]= Carbon::now();
    // }

    public function setUpdatedAtAttribute($value)
    {
    	date_default_timezone_set("America/Lima");
        $this->attributes["updated_at"]= Carbon::now();
    }

    public function product() {
        return $this->belongsTo(Product::class,"product_id");
    }
    public function unit() {
        return $this->belongsTo(Unit::class,"unit_id");
    }
    public function warehouse() {
        return $this->belongsTo(Warehouse::class,"warehouse_id");
    }
}
