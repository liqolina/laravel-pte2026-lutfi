<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HardwareEsp extends Model
{
    protected $table = 'hardware_esp';

    protected $fillable = [
        'id_esp',
        'name_esp',
        'topic_publish',
        'topic_subscribe',
    ];
}