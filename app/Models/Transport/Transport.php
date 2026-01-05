<?php

namespace App\Models\Transport;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Config\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transport extends Model
{
    use SoftDeletes;
    protected $fillable = [
        "warehouse_start_id",
        "warehouse_end_id",
        "date_emision",
        "user_id",
        "state",
        "total",
        "importe",
        "igv",
        "description",
        "date_entrega",
        "date_salida"
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

    public function transport_details(){
        return $this->hasMany(TransportDetail::class,"transport_id");
    }

    public function warehouse_start() {
        return $this->belongsTo(Warehouse::class,"warehouse_start_id");
    }

    public function warehouse_end() {
        return $this->belongsTo(Warehouse::class,"warehouse_end_id");
    }

    public function user() {
        return $this->belongsTo(User::class,"user_id");
    }

    public function getDateEmisionFormatAttribute(){
        return Carbon::parse($this->date_emision)->format("Y/m/d");
    }

    public function getDateEntregaFormatAttribute(){
        return $this->date_entrega ? Carbon::parse($this->date_entrega)->format("Y/m/d") : null;
    }

    public function getDateSalidaFormatAttribute(){
        return $this->date_salida ? Carbon::parse($this->date_salida)->format("Y/m/d") : null;
    }

    public function scopeFilterAdvance($query,$search,$warehouse_start_id,$warehouse_end_id,$unit_id,$start_date,$end_date,$search_product){
        if($search){
            $query->where("id",$search);
        }
        if($warehouse_start_id){
            $query->where("warehouse_start_id",$warehouse_start_id);
        }
        if($warehouse_end_id){
            $query->where("warehouse_end_id",$warehouse_end_id);
        }
        if($start_date && $end_date){
            $query->whereBetween("date_emision",[
                Carbon::parse($start_date)->format("Y-m-d")." 00:00:00",
                Carbon::parse($end_date)->format("Y-m-d")." 23:59:59",
            ]);
        }
        if($unit_id){
            $query->whereHas("transport_details",function($q) use($unit_id){
                $q->where("unit_id",$unit_id);
            });
        }
        if($search_product){
            $query->whereHas("transport_details",function($q) use($search_product){
                $q->whereHas("product",function($subq) use($search_product){
                    $subq->where(DB::raw("products.title || '' || products.sku"),"ilike","%".$search_product."%");
                });
            });
        }
        return $query;
    }
}
