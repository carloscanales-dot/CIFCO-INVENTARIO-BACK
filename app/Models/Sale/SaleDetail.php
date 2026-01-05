<?php

namespace App\Models\Sale;

use Carbon\Carbon;
use App\Models\Config\Unit;
use App\Models\Product\Product;
use App\Models\Config\Warehouse;
use App\Models\Config\ProductCategorie;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleDetail extends Model
{
    use SoftDeletes;
    protected $fillable = [
        "sale_id",
        "product_id",
        "product_categorie_id",
        "unit_id",
        "warehouse_id",
        "quantity",
        "price_unit",
        "discount",
        "subtotal",
        "igv",
        "total",
        "description",
        "state_attention",
        "quantity_pending",
        // "created_at",
        // "updated_at",
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

    public function sale(){
        return $this->belongsTo(Sale::class,"sale_id");
    }
    public function product(){
        return $this->belongsTo(Product::class,"product_id");
    }
    public function product_categorie(){
        return $this->belongsTo(ProductCategorie::class,"product_categorie_id");
    }
    public function unit(){
        return $this->belongsTo(Unit::class,"unit_id");
    }
    public function warehouse(){
        return $this->belongsTo(Warehouse::class,"warehouse_id");
    }
}
