<?php

namespace App\Console\Commands;

use Carbon\Carbon;
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
                    throw new \InvalidArgumentException(
                        "Invalid JSON message ID {$msg->id}: " . json_last_error_msg()
                    );
                }

                $this->validatePayloadStructure($payload, $msg->id);

                /**
                 * =========================
                 * 1. UPSERT DEVICES
                 * Match by id_esp OR mac_esp
                 * =========================
                 */
                if (!empty($payload['devices'])) {
                    foreach ($payload['devices'] as $device) {
                        $idEsp   = $this->normalizeString($device['id_esp'] ?? null);
                        $nameEsp = $this->normalizeString($device['name_esp'] ?? null);
                        $macEsp  = $this->normalizeMac($device['mac_esp'] ?? null);
                        $ipEsp   = $this->normalizeString($device['ip_esp'] ?? null);
                        $locEsp  = $this->normalizeString($device['loc_esp'] ?? null);
                        $ts      = $this->normalizeTimestamp($device['timestamp'] ?? null);

                        if (!$idEsp || !$nameEsp || !$macEsp) {
                            throw new \InvalidArgumentException(
                                "Invalid device payload structure on message ID {$msg->id}"
                            );
                        }

                        $existingById = DB::table('device_esp')
                            ->where('id_esp', $idEsp)
                            ->first();

                        if ($existingById) {
                            DB::table('device_esp')
                                ->where('id', $existingById->id)
                                ->update([
                                    'name_esp'   => $nameEsp,
                                    'mac_esp'    => $macEsp,
                                    'ip_esp'     => $ipEsp,
                                    'loc_esp'    => $locEsp,
                                    'timestamp'  => $ts,
                                    'updated_at' => now(),
                                ]);

                            continue;
                        }

                        $existingByMac = DB::table('device_esp')
                            ->where('mac_esp', $macEsp)
                            ->first();

                        if ($existingByMac) {
                            DB::table('device_esp')
                                ->where('id', $existingByMac->id)
                                ->update([
                                    'id_esp'     => $idEsp,
                                    'name_esp'   => $nameEsp,
                                    'mac_esp'    => $macEsp,
                                    'ip_esp'     => $ipEsp,
                                    'loc_esp'    => $locEsp,
                                    'timestamp'  => $ts,
                                    'updated_at' => now(),
                                ]);

                            continue;
                        }

                        DB::table('device_esp')->insert([
                            'id_esp'      => $idEsp,
                            'name_esp'    => $nameEsp,
                            'mac_esp'     => $macEsp,
                            'ip_esp'      => $ipEsp,
                            'loc_esp'     => $locEsp,
                            'timestamp'   => $ts,
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ]);
                    }
                }

                /**
                 * =========================
                 * 2. INSERT SENSORS
                 * =========================
                 */
                if (!empty($payload['sensors'])) {
                    foreach ($payload['sensors'] as $sensor) {
                        $idEsp = $this->normalizeString($sensor['id_esp'] ?? null);

                        if (
                            !$idEsp ||
                            !$this->normalizeString($sensor['id_sensor'] ?? null) ||
                            !$this->normalizeString($sensor['name_sensor'] ?? null)
                        ) {
                            throw new \InvalidArgumentException(
                                "Invalid sensor payload structure on message ID {$msg->id}"
                            );
                        }

                        $this->ensureDeviceExists($idEsp, $msg->id, 'sensor');

                        DB::table('device_sensor')->insert([
                            'id_esp'      => $idEsp,
                            'id_sensor'   => $this->normalizeString($sensor['id_sensor'] ?? null),
                            'name_sensor' => $this->normalizeString($sensor['name_sensor'] ?? null),
                            'val_A'       => $sensor['val_A'] ?? null,
                            'val_B'       => $sensor['val_B'] ?? null,
                            'val_C'       => $sensor['val_C'] ?? null,
                            'val_D'       => $sensor['val_D'] ?? null,
                            'val_E'       => $sensor['val_E'] ?? null,
                            'val_F'       => $sensor['val_F'] ?? null,
                            'val_G'       => $sensor['val_G'] ?? null,
                            'val_H'       => $sensor['val_H'] ?? null,
                            'timestamp'   => $this->normalizeTimestamp($sensor['timestamp'] ?? null),
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ]);
                    }
                }

                /**
                 * =========================
                 * 3. INSERT ACTUATORS
                 * =========================
                 */
                if (!empty($payload['actuators'])) {
                    foreach ($payload['actuators'] as $act) {
                        $idEsp = $this->normalizeString($act['id_esp'] ?? null);

                        if (
                            !$idEsp ||
                            !$this->normalizeString($act['id_act'] ?? null) ||
                            !$this->normalizeString($act['name_act'] ?? null)
                        ) {
                            throw new \InvalidArgumentException(
                                "Invalid actuator payload structure on message ID {$msg->id}"
                            );
                        }

                        $this->ensureDeviceExists($idEsp, $msg->id, 'actuator');

                        DB::table('device_act')->insert([
                            'id_esp'      => $idEsp,
                            'id_act'      => $this->normalizeString($act['id_act'] ?? null),
                            'name_act'    => $this->normalizeString($act['name_act'] ?? null),
                            'val_A'       => $act['val_A'] ?? null,
                            'val_B'       => $act['val_B'] ?? null,
                            'val_C'       => $act['val_C'] ?? null,
                            'val_D'       => $act['val_D'] ?? null,
                            'timestamp'   => $this->normalizeTimestamp($act['timestamp'] ?? null),
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ]);
                    }
                }

                /**
                 * =========================
                 * 4. INSERT EVENTS
                 * =========================
                 */
                if (!empty($payload['events'])) {
                    foreach ($payload['events'] as $event) {
                        $idEsp = $this->normalizeString($event['id_esp'] ?? null);

                        if (
                            !$idEsp ||
                            !$this->normalizeString($event['status_esp'] ?? null)
                        ) {
                            throw new \InvalidArgumentException(
                                "Invalid event payload structure on message ID {$msg->id}"
                            );
                        }

                        $this->ensureDeviceExists($idEsp, $msg->id, 'event');

                        DB::table('status_news')->insert([
                            'id_esp'      => $idEsp,
                            'status_esp'  => $this->normalizeString($event['status_esp'] ?? null),
                            'news_esp'    => $this->normalizeString($event['news_esp'] ?? null),
                            'timestamp'   => $this->normalizeTimestamp($event['timestamp'] ?? null),
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ]);
                    }
                }

                DB::table('subscriber_mqtt')
                    ->where('id', $msg->id)
                    ->delete();

                DB::commit();

                $processed++;
                $this->info("SUCCESS processed message ID: {$msg->id}");
            } catch (\InvalidArgumentException $e) {
                DB::rollBack();

                $this->deleteInvalidMessage($msg->id, $e->getMessage());
                $processed++;
            } catch (Throwable $e) {
                DB::rollBack();

                $this->error("FAILED message ID {$msg->id}: " . $e->getMessage());
            }
        }

        return $processed;
    }

    private function validatePayloadStructure(array $payload, int|string $messageId): void
    {
        $allowedKeys = ['devices', 'sensors', 'actuators', 'events'];
        $payloadKeys = array_keys($payload);

        $unknownKeys = array_diff($payloadKeys, $allowedKeys);
        if (!empty($unknownKeys)) {
            throw new \InvalidArgumentException(
                "Unknown top-level key(s) on message ID {$messageId}: " . implode(', ', $unknownKeys)
            );
        }

        $presentKeys = array_intersect($allowedKeys, $payloadKeys);
        if (empty($presentKeys)) {
            throw new \InvalidArgumentException(
                "Unsupported payload structure on message ID {$messageId}"
            );
        }

        foreach ($allowedKeys as $key) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }

            if (!is_array($payload[$key])) {
                throw new \InvalidArgumentException(
                    "Invalid structure on message ID {$messageId}: {$key} must be an array"
                );
            }

            foreach ($payload[$key] as $index => $item) {
                if (!is_array($item)) {
                    throw new \InvalidArgumentException(
                        "Invalid structure on message ID {$messageId}: {$key}[{$index}] must be an object/array"
                    );
                }
            }
        }
    }

    private function deleteInvalidMessage(int|string $messageId, string $reason): void
    {
        DB::table('subscriber_mqtt')
            ->where('id', $messageId)
            ->delete();

        $this->warn("DELETED invalid message ID {$messageId}: {$reason}");
    }

    private function ensureDeviceExists(string $idEsp, int|string $messageId, string $payloadType): void
    {
        $exists = DB::table('device_esp')
            ->where('id_esp', $idEsp)
            ->exists();

        if (!$exists) {
            throw new \RuntimeException(
                "Invalid {$payloadType} payload on message ID {$messageId}: id_esp {$idEsp} belum ada di device_esp"
            );
        }
    }

    private function normalizeTimestamp(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (
            $value === '1970-01-01 00:00:00' ||
            $value === '0000-00-00 00:00:00'
        ) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d H:i:s', $value)->format('Y-m-d H:i:s');
        } catch (Throwable $e) {
            return null;
        }
    }

    private function normalizeString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeMac(mixed $value): ?string
    {
        $value = $this->normalizeString($value);

        if ($value === null) {
            return null;
        }

        return strtoupper($value);
    }
}