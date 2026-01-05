<?php

namespace App\Models\Product;

use Carbon\Carbon;
use App\Models\Config\Unit;
use App\Models\Config\Sucursale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductWallet extends Model
{
    use SoftDeletes;
    protected $fillable = [
        "product_id",
        "type_client",
        "sucursale_id",
        "unit_id",
        "price"
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

    public function sucursale(){
        return $this->belongsTo(Sucursale::class,"sucursale_id");
    }

    public function unit(){
        return $this->belongsTo(Unit::class,"unit_id");
    }

}
