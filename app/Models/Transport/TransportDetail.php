<?php

namespace App\Models\Transport;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Config\Unit;
use App\Models\Product\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransportDetail extends Model
{
    use SoftDeletes;
    protected $fillable = [
        "transport_id",
        "product_id",
        "unit_id",
        "price_unit",
        "total",
        "quantity",
        "state",
        "user_entrega",
        "date_entrega",
        "user_salida",
        "date_salida",
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

    public function transport(){
        return $this->belongsTo(Transport::class,"transport_id");
    }
    public function product(){
        return $this->belongsTo(Product::class,"product_id");
    }
    public function unit(){
        return $this->belongsTo(Unit::class,"unit_id");
    }
    public function user_in(){
        return $this->belongsTo(User::class,"user_entrega");
    }
    public function user_out(){
        return $this->belongsTo(User::class,"user_salida");
    }
}
