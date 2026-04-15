<?php

namespace App\Models\Device;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceSensor extends Model
{
    use HasFactory;

    protected $table = 'device_sensor';

    public function device()
    {
        return $this->belongsTo(DeviceEsp::class, 'id_device', 'id');
    }
}
