<?php

namespace App\Models\Purchase;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Config\Unit;
use App\Models\Product\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseDetail extends Model
{
    use SoftDeletes;
    protected $fillable = [
        "purchase_id",
        "product_id",
        "unit_id",
        "price_unit",
        "total",
        "quantity",
        "state",
        "user_entrega",
        "date_entrega",
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

    public function purchase(){
        return $this->belongsTo(Purchase::class,"purchase_id");
    }
    public function product(){
        return $this->belongsTo(Product::class,"product_id");
    }
    public function unit(){
        return $this->belongsTo(Unit::class,"unit_id");
    }
    public function user(){
        return $this->belongsTo(User::class,"user_entrega");
    }
}
