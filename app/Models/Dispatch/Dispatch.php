<?php

namespace App\Models\Dispatch;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Config\Warehouse;
use App\Models\User;
use App\Models\Client\Client;

class Dispatch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'warehouse_id',
        'requester_id',
        'user_id',
        'sucursale_id',
        'requisition_number',
        'area_id',
        'reference',
        'date_emision',
        'description',
        'state',
    ];

    protected $casts = [
        'date_emision' => 'datetime',
    ];

    public function details()
    {
        return $this->hasMany(DispatchDetail::class, 'dispatch_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function requester()
    {
        return $this->belongsTo(Client::class, 'requester_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
