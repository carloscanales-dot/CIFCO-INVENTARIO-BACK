<?php

namespace App\Models\Sale;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Client\Client;
use App\Models\Config\Sucursale;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\Sale\SaleFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sale extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        "user_id",
        "client_id",
        "type_client",
        "sucursale_id",
        "subtotal",
        "total",
        "igv",
        "state_sale",
        "state_payment",
        "debt",
        "paid_out",
        "date_validation",
        "date_pay_complete",
        "description",
        "state_entrega",
        "discount",
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

    protected static function newFactory()
    {
        return SaleFactory::new();
    }

    public function user(){
        return $this->belongsTo(User::class,"user_id");
    }

    public function client(){
        return $this->belongsTo(Client::class,"client_id");
    }

    public function sucursale(){
        return $this->belongsTo(Sucursale::class,"sucursale_id");
    }

    public function sale_details() {
        return $this->hasMany(SaleDetail::class,"sale_id");
    }

    public function payments() {
        return $this->hasMany(SalePayment::class,"sale_id");
    }

    public function getFirstPaymentAttribute() {
        return $this->payments->first();
    }

    public function scopeFilterAdvance(
    $query,
    $search = null,
    $type_client = null,
    $search_client = null,
    $start_date = null,
    $end_date = null,
    $type = null,
    $state_entrega = null,
    $state_payment = null,
    $search_product = null,
    $user = null
    ){
        if($search){
            $query->where("id",$search);
        }
        if($type_client){
            $query->where("type_client",$type_client);
        }
        if($search_client){
            $query->whereHas("client",function($q) use($search_client){
                $q->where(DB::raw("clients.full_name || '' || clients.n_document || '' || clients.phone"),"ilike","%".$search_client."%");
            });
        }
        if($start_date && $end_date){
            $query->whereBetween("created_at",[Carbon::parse($start_date)->format("Y-m-d")." 00:00:00",
                                            Carbon::parse($end_date)->format("Y-m-d")." 23:59:59"]);
        }
        if($type){
            $query->where("state_sale",$type);
        }
        if($state_entrega){
            $query->where("state_entrega",$state_entrega);
        }
        if($state_payment){
            $query->where("state_payment",$state_payment);
        }
        if($search_product){
            $query->whereHas("sale_details",function($q) use($search_product){
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
