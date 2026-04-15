<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublishMqtt extends Model
{
    protected $table = 'publish_mqtt';

    protected $fillable = [
        'id_esp',
        'topic_publish',
        'message',
    ];
}