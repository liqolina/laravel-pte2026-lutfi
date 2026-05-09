<?php

namespace App\Console\Commands\MQTT;

use App\Models\PublishMqtt;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\Contracts\MqttClient as MqttClientContract;
use PhpMqtt\Client\Facades\MQTT;
use Throwable;

class MqttPublishWorker extends Command
{
    protected $signature = 'mqtt:publish-worker
                            {--sleep=1 : Delay in seconds if queue is empty}
                            {--batch=100 : Max rows processed per cycle}
                            {--qos=1 : QoS level (0,1,2)}
                            {--retain=0 : Retain flag (0/1)}
                            {--queue-wait=5 : Seconds to wait for QoS ack}';

    protected $description = 'Publish queued MQTT messages to publish broker and delete successful rows.';

    public function handle(): int
    {
        $sleep = max(1, (int) $this->option('sleep'));
        $batch = max(1, (int) $this->option('batch'));
        $qos = max(0, min(2, (int) $this->option('qos')));
        $retain = (bool) ((int) $this->option('retain'));
        $queueWait = max(1, (int) $this->option('queue-wait'));

        $this->info('MQTT publish worker started.');

        while (true) {
            try {
                $rows = PublishMqtt::query()
                    ->with('hardware')
                    ->orderBy('id')
                    ->limit($batch)
                    ->get();

                if ($rows->isEmpty()) {
                    sleep($sleep);
                    continue;
                }

                /** @var MqttClientContract $mqtt */
                $mqtt = MQTT::connection('mqtt_pub');

                foreach ($rows as $row) {
                    $topic = trim((string) optional($row->hardware)->topic_publish);
                    $message = (string) $row->message;

                    if ($topic === '') {
                        Log::warning('MQTT publish skipped because hardware_esp.topic_publish is empty.', [
                            'publish_mqtt_id' => $row->id,
                            'id_esp' => $row->id_esp,
                        ]);
                        continue;
                    }

                    try {
                        $mqtt->publish($topic, $message, $qos, $retain);

                        if ($qos > 0) {
                            $mqtt->loop(true, true, $queueWait);
                        }

                        $row->delete();

                        Log::info('MQTT publish success.', [
                            'publish_mqtt_id' => $row->id,
                            'id_esp' => $row->id_esp,
                            'topic' => $topic,
                            'qos' => $qos,
                        ]);
                    } catch (Throwable $e) {
                        Log::error('MQTT publish failed for a queue row.', [
                            'publish_mqtt_id' => $row->id,
                            'id_esp' => $row->id_esp,
                            'topic' => $topic,
                            'error' => $e->getMessage(),
                        ]);

                        try {
                            $mqtt->disconnect();
                        } catch (Throwable) {
                        }

                        sleep($sleep);
                        continue 2;
                    }
                }

                try {
                    $mqtt->disconnect();
                } catch (Throwable) {
                }

            } catch (Throwable $e) {
                Log::error('MQTT publish worker crashed and will retry.', [
                    'error' => $e->getMessage(),
                ]);

                sleep($sleep);
            }
        }
    }
}