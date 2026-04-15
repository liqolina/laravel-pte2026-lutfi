<?php

namespace App\Models\Device;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceEsp extends Model
{
    use HasFactory;

    protected $table = 'device_esp';

    public function sensors()
    {
        return $this->hasMany(DeviceSensor::class, 'id_device', 'id');
    }

    public function acts()
    {
        return $this->hasMany(DeviceAct::class, 'id_device', 'id');
    }
}
