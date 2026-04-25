<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpMqtt\Client\Facades\MQTT;

class MqttBroker1SimpleSubscribeCommand extends Command
{
    protected $signature = 'mqtt:broker1:subscribe-simple {topic=test/broker1}';
    protected $description = 'Simple subscriber for broker 1';

    public function handle(): int
    {
        $topic = (string) $this->argument('topic');

        $mqtt = MQTT::connection('mosquitto1');

        $this->info("Broker 1 simple subscriber listening on: {$topic}");

        $mqtt->subscribe($topic, function (string $receivedTopic, string $message) {
            logger()->info('Broker 1 incoming message', [
                'topic' => $receivedTopic,
                'message' => $message,
            ]);

            $this->line("[{$receivedTopic}] {$message}");
        }, 0);

        $mqtt->loop(true);

        return self::SUCCESS;
    }
}