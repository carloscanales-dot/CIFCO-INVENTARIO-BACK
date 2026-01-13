<?php

namespace App\Models\Dispatch;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Product\Product;
use App\Models\Config\Warehouse;
use App\Models\Config\Unit;


class DispatchDetail extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'dispatch_id',
        'product_id',
        'warehouse_id',
        'unit_id',
        'quantity',
    ];

    public function dispatch()
    {
        return $this->belongsTo(Dispatch::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
