<?php

namespace App\Console\Commands\MQTT;

use App\Models\MQTT\HardwareEsp;
use App\Models\MQTT\SubcriberMqtt;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\Contracts\MqttClient as MqttClientContract;
use PhpMqtt\Client\Facades\MQTT;
use Throwable;

class MqttSubscribeWorker extends Command
{
    protected $signature = 'mqtt:subscribe-worker {--qos=1} {--sync=2}';
    protected $description = 'Dynamic MQTT subscriber from hardware_esp';

    protected array $activeSubscriptions = [];
    protected array $topicOwnerMap = [];
    protected int $lastSyncAt = 0;

    public function handle(): int
    {
        $qos = (int) $this->option('qos');
        $syncInterval = max(1, (int) $this->option('sync'));

        while (true) {
            try {
                /** @var MqttClientContract $mqtt */
                $mqtt = MQTT::connection('mqtt_sub');

                // Sync pertama saat startup
                $this->syncSubscriptions($mqtt, $qos);

                // Sync berkala selama loop berjalan
                $mqtt->registerLoopEventHandler(function (MqttClientContract $client, float $elapsedTime) use ($qos, $syncInterval) {
                    if ((time() - $this->lastSyncAt) >= $syncInterval) {
                        $this->syncSubscriptions($client, $qos);
                    }
                });

                $this->info('MQTT subscriber connected and looping...');
                $mqtt->loop(true);

            } catch (Throwable $e) {
                Log::error('Subscriber worker error', [
                    'message' => $e->getMessage(),
                ]);

                sleep(3);
            }
        }
    }

    protected function syncSubscriptions(MqttClientContract $mqtt, int $qos): void
    {
        $this->lastSyncAt = time();

        $rows = HardwareEsp::query()
            ->select(['id_esp', 'topic_subcribe'])
            ->whereNotNull('topic_subcribe')
            ->get();

        $desiredTopics = [];
        $newOwnerMap = [];

        foreach ($rows as $row) {
            $topic = trim((string) $row->topic_subcribe);

            if ($topic === '') {
                continue;
            }

            $desiredTopics[$topic] = true;
            $newOwnerMap[$topic] = $row->id_esp;
        }

        // Subscribe topic baru
        foreach (array_keys(array_diff_key($desiredTopics, $this->activeSubscriptions)) as $topic) {
            $mqtt->subscribe($topic, function (string $topic, string $message, bool $retained, array $matchedWildcards) {
                SubcriberMqtt::create([
                    'id_esp' => $this->topicOwnerMap[$topic] ?? '',
                    'topic_subcribe' => $topic,
                    'message' => $message,
                    'timestamp' => now(),
                ]);
            }, $qos);

            Log::info("Subscribed: {$topic}");
            $this->activeSubscriptions[$topic] = true;
        }

        // Unsubscribe topic yang sudah dihapus dari DB
        foreach (array_keys(array_diff_key($this->activeSubscriptions, $desiredTopics)) as $topic) {
            $mqtt->unsubscribe($topic);
            unset($this->activeSubscriptions[$topic]);

            Log::info("Unsubscribed: {$topic}");
        }

        $this->topicOwnerMap = $newOwnerMap;
    }
}