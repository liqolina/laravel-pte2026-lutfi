<?php

namespace App\Models\MQTT;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HardwareEsp extends Model
{
    protected $table = 'hardware_esp';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    public function publishQueues(): HasMany
    {
        return $this->hasMany(PublishMqtt::class, 'id_esp', 'id_esp');
    }

    public function subscriberLogs(): HasMany
    {
        return $this->hasMany(SubcriberMqtt::class, 'id_esp', 'id_esp');
    }
}