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
    protected int $lastHardwareStateHash = 0;

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
            ->select(['id', 'id_esp', 'topic_subcribe'])
            ->whereNotNull('topic_subcribe')
            ->get();

        $desiredTopics = [];
        $newOwnerMap = [];
        $stateHash = crc32(json_encode($rows->map(fn ($row) => [
            'id' => $row->id,
            'id_esp' => $row->id_esp,
            'topic_subcribe' => $row->topic_subcribe,
        ])->values()->all()));

        if ($stateHash === $this->lastHardwareStateHash && !empty($this->activeSubscriptions)) {
            return;
        }

        $this->lastHardwareStateHash = $stateHash;

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
                try {
                    SubcriberMqtt::create([
                        'id_esp' => $this->topicOwnerMap[$topic] ?? '',
                        'topic_subcribe' => $topic,
                        'message' => $message,
                        'timestamp' => now(),
                    ]);

                    Log::info('Incoming MQTT message stored.', [
                        'id_esp' => $this->topicOwnerMap[$topic] ?? '',
                        'topic' => $topic,
                    ]);
                } catch (Throwable $e) {
                    Log::error('Failed to store incoming MQTT message.', [
                        'topic' => $topic,
                        'error' => $e->getMessage(),
                    ]);
                }
            }, $qos);

            Log::info('Subscribed to MQTT topic.', [
                'topic' => $topic,
                'id_esp' => $this->topicOwnerMap[$topic] ?? null,
                'qos' => $qos,
            ]);
            $this->activeSubscriptions[$topic] = true;
        }

        // Unsubscribe topic yang sudah dihapus dari DB
        foreach (array_keys(array_diff_key($this->activeSubscriptions, $desiredTopics)) as $topic) {
            try {
                $mqtt->unsubscribe($topic);
                Log::info('Unsubscribed from MQTT topic.', [
                    'topic' => $topic,
                ]);
            } catch (Throwable $e) {
                Log::error('Failed to unsubscribe from MQTT topic.', [
                    'topic' => $topic,
                    'error' => $e->getMessage(),
                ]);
            }

            unset($this->activeSubscriptions[$topic]);
        }

        $this->topicOwnerMap = $newOwnerMap;
    }
}