<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubcriberMqtt extends Model
{
    protected $table = 'subcriber_mqtt';

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