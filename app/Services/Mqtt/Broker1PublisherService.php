<?php

namespace App\Services\Mqtt;

use App\Models\PublishMqtt;
use PhpMqtt\Client\Facades\MQTT;
use Throwable;

class Broker1PublisherService
{
    public function flush(int $limit = 100): int
    {
        $rows = PublishMqtt::query()
            ->join('hardware_esp', 'hardware_esp.id_esp', '=', 'publish_mqtt.id_esp')
            ->select([
                'publish_mqtt.id',
                'publish_mqtt.id_esp',
                'publish_mqtt.message',
                'hardware_esp.topic_publish as resolved_topic',
            ])
            ->whereNotNull('hardware_esp.topic_publish')
            ->where('hardware_esp.topic_publish', '!=', '')
            ->orderBy('publish_mqtt.id')
            ->limit($limit)
            ->get();

        if ($rows->isEmpty()) {
            return 0;
        }

        $mqtt = MQTT::connection('mosquitto1');
        $sent = 0;

        try {
            foreach ($rows as $row) {
                $mqtt->publish(
                    topic: (string) $row->resolved_topic,
                    message: (string) $row->message,
                    qualityOfService: 0,
                    retain: false
                );

                PublishMqtt::whereKey($row->id)->delete();
                $sent++;
            }
        } finally {
            if ($mqtt->isConnected()) {
                $mqtt->disconnect();
            }
        }

        return $sent;
    }
}