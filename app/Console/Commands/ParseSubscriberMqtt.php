<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ParseSubscriberMqtt extends Command
{
    protected $signature = 'mqtt:parse-subscriber 
                            {--limit=100 : Jumlah message yang diproses per batch}
                            {--sleep=1 : Jeda detik jika tidak ada message}
                            {--once : Jalankan sekali saja tanpa loop}';

    protected $description = 'Loop parse subscriber_mqtt JSON → sinkron ke device_esp, insert sensor/actuator/event, lalu delete message';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $sleep = max(1, (int) $this->option('sleep'));

        $this->info('MQTT Subscriber Parser started...');
        $this->info("Limit per batch: {$limit}");
        $this->info("Sleep when empty: {$sleep} second(s)");

        while (true) {
            $processed = $this->processBatch($limit);

            if ($this->option('once')) {
                break;
            }

            if ($processed === 0) {
                $this->line("No MQTT messages found. Sleeping {$sleep} second(s)...");
                sleep($sleep);
            }
        }

        return self::SUCCESS;
    }

    private function processBatch(int $limit): int
    {
        $messages = DB::table('subscriber_mqtt')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($messages->isEmpty()) {
            return 0;
        }

        $processed = 0;

        foreach ($messages as $msg) {
            try {
                DB::beginTransaction();

                $payload = json_decode($msg->message, true);

                if (!is_array($payload)) {
                    throw new \Exception("Invalid JSON message ID {$msg->id}: " . json_last_error_msg());
                }

                /**
                 * =========================
                 * 1. UPSERT DEVICES
                 * Format JSON:
                 * devices[] = id_esp, name_esp, mac_esp, ip_esp, loc_esp, timestamp
                 * =========================
                 */
                if (!empty($payload['devices']) && is_array($payload['devices'])) {
                    foreach ($payload['devices'] as $device) {
                        $idEsp = $device['id_esp'] ?? null;
                        $nameEsp = $device['name_esp'] ?? null;
                        $macEsp = $device['mac_esp'] ?? null;

                        if (!$idEsp || !$nameEsp || !$macEsp) {
                            throw new \Exception("Invalid device payload on message ID {$msg->id}");
                        }

                        $exists = DB::table('device_esp')
                            ->where('id_esp', $idEsp)
                            ->exists();

                        if ($exists) {
                            DB::table('device_esp')
                                ->where('id_esp', $idEsp)
                                ->update([
                                    'name_esp' => $nameEsp,
                                    'mac_esp' => $macEsp,
                                    'ip_esp' => $device['ip_esp'] ?? null,
                                    'loc_esp' => $device['loc_esp'] ?? null,
                                    'timestamp' => $device['timestamp'] ?? null,
                                    'updated_at' => now(),
                                ]);
                        } else {
                            DB::table('device_esp')->insert([
                                'id_esp' => $idEsp,
                                'name_esp' => $nameEsp,
                                'mac_esp' => $macEsp,
                                'ip_esp' => $device['ip_esp'] ?? null,
                                'loc_esp' => $device['loc_esp'] ?? null,
                                'timestamp' => $device['timestamp'] ?? null,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }

                /**
                 * =========================
                 * 2. INSERT SENSORS
                 * Format JSON:
                 * sensors[] = id_esp, id_sensor, name_sensor, val_A ... val_H, timestamp
                 * =========================
                 */
                if (!empty($payload['sensors']) && is_array($payload['sensors'])) {
                    foreach ($payload['sensors'] as $sensor) {
                        $idEsp = $sensor['id_esp'] ?? null;

                        if (!$idEsp || empty($sensor['id_sensor']) || empty($sensor['name_sensor'])) {
                            throw new \Exception("Invalid sensor payload on message ID {$msg->id}");
                        }

                        $this->ensureDeviceExists($idEsp, $msg->id, 'sensor');

                        DB::table('device_sensor')->insert([
                            'id_esp' => $idEsp,
                            'id_sensor' => $sensor['id_sensor'],
                            'name_sensor' => $sensor['name_sensor'],
                            'val_A' => $sensor['val_A'] ?? null,
                            'val_B' => $sensor['val_B'] ?? null,
                            'val_C' => $sensor['val_C'] ?? null,
                            'val_D' => $sensor['val_D'] ?? null,
                            'val_E' => $sensor['val_E'] ?? null,
                            'val_F' => $sensor['val_F'] ?? null,
                            'val_G' => $sensor['val_G'] ?? null,
                            'val_H' => $sensor['val_H'] ?? null,
                            'timestamp' => $sensor['timestamp'] ?? null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                /**
                 * =========================
                 * 3. INSERT ACTUATORS
                 * Format JSON:
                 * actuators[] = id_esp, id_act, name_act, val_A ... val_D, timestamp
                 * =========================
                 */
                if (!empty($payload['actuators']) && is_array($payload['actuators'])) {
                    foreach ($payload['actuators'] as $act) {
                        $idEsp = $act['id_esp'] ?? null;

                        if (!$idEsp || empty($act['id_act']) || empty($act['name_act'])) {
                            throw new \Exception("Invalid actuator payload on message ID {$msg->id}");
                        }

                        $this->ensureDeviceExists($idEsp, $msg->id, 'actuator');

                        DB::table('device_act')->insert([
                            'id_esp' => $idEsp,
                            'id_act' => $act['id_act'],
                            'name_act' => $act['name_act'],
                            'val_A' => $act['val_A'] ?? null,
                            'val_B' => $act['val_B'] ?? null,
                            'val_C' => $act['val_C'] ?? null,
                            'val_D' => $act['val_D'] ?? null,
                            'timestamp' => $act['timestamp'] ?? null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                /**
                 * =========================
                 * 4. INSERT EVENTS → status_news
                 * Format JSON:
                 * events[] = id_esp, status_esp, news_esp, timestamp
                 * =========================
                 */
                if (!empty($payload['events']) && is_array($payload['events'])) {
                    foreach ($payload['events'] as $event) {
                        $idEsp = $event['id_esp'] ?? null;

                        if (!$idEsp || empty($event['status_esp'])) {
                            throw new \Exception("Invalid event payload on message ID {$msg->id}");
                        }

                        $this->ensureDeviceExists($idEsp, $msg->id, 'event');

                        DB::table('status_news')->insert([
                            'id_esp' => $idEsp,
                            'status_esp' => $event['status_esp'],
                            'news_esp' => $event['news_esp'] ?? null,
                            'timestamp' => $event['timestamp'] ?? null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                /**
                 * =========================
                 * 5. DELETE MESSAGE IF SUCCESS
                 * =========================
                 */
                DB::table('subscriber_mqtt')
                    ->where('id', $msg->id)
                    ->delete();

                DB::commit();

                $processed++;

                $this->info("SUCCESS processed message ID: {$msg->id}");
            } catch (Throwable $e) {
                DB::rollBack();

                $this->error("FAILED message ID {$msg->id}: " . $e->getMessage());
            }
        }

        return $processed;
    }

    private function ensureDeviceExists(string $idEsp, int|string $messageId, string $payloadType): void
    {
        $exists = DB::table('device_esp')
            ->where('id_esp', $idEsp)
            ->exists();

        if (!$exists) {
            throw new \Exception("Invalid {$payloadType} payload on message ID {$messageId}: id_esp {$idEsp} belum ada di device_esp");
        }
    }
}