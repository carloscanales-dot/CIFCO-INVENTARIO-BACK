<?php

namespace App\Models\Config;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class UnitConversion extends Model
{
    protected $fillable = [
        "unit_id",
        "unit_to_id",
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

    // LA UNIDAD A LA QUE ESTA RELACIONADA
    public function unit(){
        return $this->belongsTo(Unit::class,"unit_id");
    }

    // LA UNIDAD A LA QUE se puede convertir
    public function unit_to(){
        return $this->belongsTo(Unit::class,"unit_to_id");
    }
}
