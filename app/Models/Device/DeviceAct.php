<?php

namespace App\Models\Device;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceAct extends Model
{
    use HasFactory;

    protected $table = 'device_act';

    public function device()
    {
        return $this->belongsTo(DeviceEsp::class, 'id_device', 'id');
    }
}
