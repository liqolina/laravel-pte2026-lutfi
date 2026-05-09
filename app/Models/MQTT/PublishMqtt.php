<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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