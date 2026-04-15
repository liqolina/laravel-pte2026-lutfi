<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ParseSubscriberMqttToDeviceTables extends Command
{
    protected $signature = 'mqtt:parse-subscriber {--limit=100 : Maksimal pesan subscriber_mqtt yang diproses}';

    protected $description = 'Parse JSON dari subscriber_mqtt.message ke device_esp, device_sensor, dan device_act. Jika sukses, pesan dihapus.';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $ids = DB::table('subscriber_mqtt')
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        if ($ids->isEmpty()) {
            $this->info('Tidak ada pesan subscriber_mqtt yang perlu diproses.');
            return self::SUCCESS;
        }

        $success = 0;
        $failed = 0;

        foreach ($ids as $id) {
            try {
                DB::transaction(function () use ($id) {
                    $subscriber = DB::table('subscriber_mqtt')
                        ->where('id', $id)
                        ->lockForUpdate()
                        ->first();

                    if (! $subscriber) {
                        return;
                    }

                    $payload = json_decode(
                        $subscriber->message,
                        true,
                        512,
                        JSON_THROW_ON_ERROR
                    );

                    $this->validatePayload($payload, $subscriber->id);

                    $this->saveDevices($payload['devices'] ?? []);
                    $this->saveSensors($payload['sensors'] ?? []);
                    $this->saveActuators($payload['actuators'] ?? []);

                    DB::table('subscriber_mqtt')
                        ->where('id', $subscriber->id)
                        ->delete();
                }, 3);

                $success++;
                $this->info("Pesan subscriber_mqtt ID {$id} berhasil diproses dan dihapus.");
            } catch (Throwable $e) {
                $failed++;
                report($e);

                $this->error("Gagal memproses subscriber_mqtt ID {$id}: {$e->getMessage()}");
            }
        }

        $this->info("Selesai. Sukses: {$success}, gagal: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function validatePayload(mixed $payload, int|string $subscriberId): void
    {
        if (! is_array($payload)) {
            throw new RuntimeException("Payload subscriber_mqtt ID {$subscriberId} bukan JSON object.");
        }

        foreach (['devices', 'sensors', 'actuators'] as $key) {
            if (array_key_exists($key, $payload) && ! is_array($payload[$key])) {
                throw new RuntimeException("Field {$key} harus berupa array.");
            }
        }

        if (
            empty($payload['devices'] ?? []) &&
            empty($payload['sensors'] ?? []) &&
            empty($payload['actuators'] ?? [])
        ) {
            throw new RuntimeException('Payload kosong. Minimal harus ada devices, sensors, atau actuators.');
        }
    }

    private function saveDevices(array $devices): void
    {
        foreach ($devices as $index => $device) {
            if (! is_array($device)) {
                throw new RuntimeException("Data devices index {$index} tidak valid.");
            }

            $idEsp = $this->requiredString($device, 'id_esp', "devices index {$index}");

            $this->updateOrInsertWithTimestamps(
                'device_esp',
                ['id_esp' => $idEsp],
                [
                    'name_esp' => $this->requiredString($device, 'name_esp', "devices {$idEsp}"),
                    'mac_esp' => $this->requiredString($device, 'mac_esp', "devices {$idEsp}"),
                    'ip_esp' => $this->nullableString($device, 'ip_esp'),
                    'loc_esp' => $this->nullableString($device, 'loc_esp'),
                    'status_esp' => $this->requiredString($device, 'status_esp', "devices {$idEsp}"),
                    'news_esp' => $this->nullableString($device, 'news_esp'),
                    'log_time' => $this->nullableTimestamp($device, 'log_time'),
                ]
            );
        }
    }

    private function saveSensors(array $sensors): void
    {
        foreach ($sensors as $index => $sensor) {
            if (! is_array($sensor)) {
                throw new RuntimeException("Data sensors index {$index} tidak valid.");
            }

            $idEsp = $this->requiredString($sensor, 'id_esp', "sensors index {$index}");
            $idSensor = $this->requiredString($sensor, 'id_sensor', "sensors index {$index}");

            $this->ensureDeviceExists($idEsp, "sensor {$idSensor}");

            $this->updateOrInsertWithTimestamps(
                'device_sensor',
                [
                    'id_esp' => $idEsp,
                    'id_sensor' => $idSensor,
                ],
                [
                    'name_sensor' => $this->requiredString($sensor, 'name_sensor', "sensor {$idSensor}"),
                    'val_A' => $this->nullableDecimal($sensor, 'val_A', "sensor {$idSensor}"),
                    'val_B' => $this->nullableDecimal($sensor, 'val_B', "sensor {$idSensor}"),
                    'val_C' => $this->nullableDecimal($sensor, 'val_C', "sensor {$idSensor}"),
                    'val_D' => $this->nullableDecimal($sensor, 'val_D', "sensor {$idSensor}"),
                    'val_E' => $this->nullableDecimal($sensor, 'val_E', "sensor {$idSensor}"),
                    'val_F' => $this->nullableDecimal($sensor, 'val_F', "sensor {$idSensor}"),
                    'val_G' => $this->nullableDecimal($sensor, 'val_G', "sensor {$idSensor}"),
                    'val_H' => $this->nullableDecimal($sensor, 'val_H', "sensor {$idSensor}"),
                    'timestamp' => $this->nullableTimestamp($sensor, 'timestamp'),
                ]
            );
        }
    }

    private function saveActuators(array $actuators): void
    {
        foreach ($actuators as $index => $actuator) {
            if (! is_array($actuator)) {
                throw new RuntimeException("Data actuators index {$index} tidak valid.");
            }

            $idEsp = $this->requiredString($actuator, 'id_esp', "actuators index {$index}");
            $idAct = $this->requiredString($actuator, 'id_act', "actuators index {$index}");

            $this->ensureDeviceExists($idEsp, "actuator {$idAct}");

            $this->updateOrInsertWithTimestamps(
                'device_act',
                [
                    'id_esp' => $idEsp,
                    'id_act' => $idAct,
                ],
                [
                    'name_act' => $this->requiredString($actuator, 'name_act', "actuator {$idAct}"),
                    'val_A' => $this->nullableDecimal($actuator, 'val_A', "actuator {$idAct}"),
                    'val_B' => $this->nullableDecimal($actuator, 'val_B', "actuator {$idAct}"),
                    'val_C' => $this->nullableDecimal($actuator, 'val_C', "actuator {$idAct}"),
                    'val_D' => $this->nullableDecimal($actuator, 'val_D', "actuator {$idAct}"),
                    'timestamp' => $this->nullableTimestamp($actuator, 'timestamp'),
                ]
            );
        }
    }

    private function updateOrInsertWithTimestamps(string $table, array $keys, array $values): void
    {
        $now = now();

        $exists = DB::table($table)
            ->where($keys)
            ->exists();

        $values['updated_at'] = $now;

        if (! $exists) {
            $values['created_at'] = $now;
        }

        DB::table($table)->updateOrInsert($keys, $values);
    }

    private function ensureDeviceExists(string $idEsp, string $context): void
    {
        $exists = DB::table('device_esp')
            ->where('id_esp', $idEsp)
            ->exists();

        if (! $exists) {
            throw new RuntimeException("id_esp {$idEsp} untuk {$context} belum ada di device_esp.");
        }
    }

    private function requiredString(array $row, string $key, string $context): string
    {
        if (! array_key_exists($key, $row) || $row[$key] === null || $row[$key] === '') {
            throw new RuntimeException("Field {$key} wajib diisi pada {$context}.");
        }

        return (string) $row[$key];
    }

    private function nullableString(array $row, string $key): ?string
    {
        if (! array_key_exists($key, $row) || $row[$key] === null || $row[$key] === '') {
            return null;
        }

        return (string) $row[$key];
    }

    private function nullableDecimal(array $row, string $key, string $context): ?float
    {
        if (! array_key_exists($key, $row) || $row[$key] === null || $row[$key] === '') {
            return null;
        }

        if (! is_numeric($row[$key])) {
            throw new RuntimeException("Field {$key} pada {$context} harus angka.");
        }

        return (float) $row[$key];
    }

    private function nullableTimestamp(array $row, string $key): ?string
    {
        if (! array_key_exists($key, $row) || $row[$key] === null || $row[$key] === '') {
            return null;
        }

        return Carbon::parse($row[$key])->format('Y-m-d H:i:s');
    }
}