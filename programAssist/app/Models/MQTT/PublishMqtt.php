<?php

namespace App\Models\MQTT;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\MQTT\HardwareEsp;

class PublishMqtt extends Model
{
    protected $table = 'publish_mqtt';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    public function hardware(): BelongsTo
    {
        return $this->belongsTo(HardwareEsp::class, 'id_esp', 'id_esp');
    }
}