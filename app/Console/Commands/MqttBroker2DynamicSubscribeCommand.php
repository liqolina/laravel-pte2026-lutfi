<?php

namespace App\Console\Commands;

use App\Models\HardwareEsp;
use App\Models\SubscriberMqtt;
use Illuminate\Console\Command;
use PhpMqtt\Client\Contracts\MqttClient as MqttClientContract;
use PhpMqtt\Client\Facades\MQTT;
use Throwable;

class MqttBroker2DynamicSubscribeCommand extends Command
{
    protected $signature = 'mqtt:broker2:subscribe-dynamic {--sync=5}';
    protected $description = 'Dynamic subscriber for broker 2 based on topics in hardware_esp table';

    private bool $running = true;

    /**
     * @var array<string, bool>
     */
    private array $activeTopics = [];

    public function handle(): int
    {
        $syncSeconds = max(1, (int) $this->option('sync'));

        /** @var MqttClientContract $mqtt */
        $mqtt = MQTT::connection('mosquitto2');

        $this->trapSignals();

        $this->info('Broker 2 dynamic subscriber started.');

        $loopStartedAt = microtime(true);
        $lastSyncAt = 0.0;

        try {
            while ($this->running) {
                $now = microtime(true);

                if (($now - $lastSyncAt) >= $syncSeconds) {
                    $this->syncSubscriptions($mqtt);
                    $lastSyncAt = $now;
                }

                $mqtt->loopOnce($loopStartedAt, true, 200000);
            }
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            throw $e;
        } finally {
            if ($mqtt->isConnected()) {
                $mqtt->disconnect();
            }
        }

        $this->info('Broker 2 dynamic subscriber stopped.');

        return self::SUCCESS;
    }

    private function syncSubscriptions(MqttClientContract $mqtt): void
    {
        $dbTopics = HardwareEsp::query()
            ->whereNotNull('topic_subscribe')
            ->where('topic_subscribe', '!=', '')
            ->distinct()
            ->pluck('topic_subscribe')
            ->map(fn ($topic) => trim((string) $topic))
            ->filter()
            ->values()
            ->all();

        $dbTopicMap = array_fill_keys($dbTopics, true);

        $topicsToSubscribe = array_diff_key($dbTopicMap, $this->activeTopics);
        $topicsToUnsubscribe = array_diff_key($this->activeTopics, $dbTopicMap);

        foreach (array_keys($topicsToSubscribe) as $topic) {
            $mqtt->subscribe($topic, function (
                string $receivedTopic,
                string $message,
                bool $retained = false,
                array $matchedWildcards = []
            ) {
                $devices = HardwareEsp::query()
                    ->where('topic_subscribe', $receivedTopic)
                    ->get(['id_esp']);

                if ($devices->isEmpty()) {
                    SubscriberMqtt::create([
                        'id_esp' => null,
                        'topic_subscribe' => $receivedTopic,
                        'message' => $message,
                    ]);
                } else {
                    foreach ($devices as $device) {
                        SubscriberMqtt::create([
                            'id_esp' => $device->id_esp,
                            'topic_subscribe' => $receivedTopic,
                            'message' => $message,
                        ]);
                    }
                }

                $this->line("[{$receivedTopic}] {$message}");
            }, 0);

            $this->activeTopics[$topic] = true;
            $this->info("Subscribed: {$topic}");
        }

        foreach (array_keys($topicsToUnsubscribe) as $topic) {
            $mqtt->unsubscribe($topic);
            unset($this->activeTopics[$topic]);
            $this->warn("Unsubscribed: {$topic}");
        }
    }

    private function trapSignals(): void
    {
        if (! function_exists('pcntl_async_signals')) {
            return;
        }

        pcntl_async_signals(true);

        pcntl_signal(SIGINT, function () {
            $this->running = false;
        });

        pcntl_signal(SIGTERM, function () {
            $this->running = false;
        });
    }
}