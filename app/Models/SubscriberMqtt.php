<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriberMqtt extends Model
{
    protected $table = 'subscriber_mqtt';

    protected $fillable = [
        'id_esp',
        'topic_subscribe',
        'message',
    ];
}