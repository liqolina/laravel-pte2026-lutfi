<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpMqtt\Client\Facades\MQTT;

class MqttBroker2SimplePublishCommand extends Command
{
    protected $signature = 'mqtt:broker2:publish {topic} {message} {--retain}';
    protected $description = 'Simple publisher for broker 2';

    public function handle(): int
    {
        $topic = (string) $this->argument('topic');
        $message = (string) $this->argument('message');
        $retain = (bool) $this->option('retain');

        MQTT::publish($topic, $message, $retain, 'mosquitto2');
        MQTT::disconnect('mosquitto2');

        $this->info("Published to broker 2: [{$topic}] {$message}");

        return self::SUCCESS;
    }
}