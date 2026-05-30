<?php

namespace App\Imports;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class FullDeviceCsvImport
{
    /**
     * Header terbaru yang benar sesuai schema terbaru.
     * device_esp menyimpan timestamp device, sedangkan status ada di status_news.
     */
    protected array $flatColumnsCurrent = [
        'id',
        'device_id',
        'id_esp',
        'name_esp',
        'mac_esp',
        'ip_esp',
        'loc_esp',
        'device_timestamp',
        'device_created_at',
        'device_updated_at',
        'status_id',
        'status_esp',
        'news_esp',
        'status_timestamp',
        'status_created_at',
        'status_updated_at',
        'sensor_id',
        'sensor_code',
        'sensor_name',
        'val_A',
        'val_B',
        'val_C',
        'val_D',
        'val_E',
        'val_F',
        'val_G',
        'val_H',
        'sensor_timestamp',
        'act_id',
        'act_code',
        'act_name',
        'act_val_A',
        'act_val_B',
        'act_val_C',
        'act_val_D',
        'act_timestamp',
    ];

    /**
     * Format lama yang sempat memakai status/news di device_esp tetap didukung.
     */
    protected array $flatColumnsCompatDeviceStatus = [
        'id',
        'id_esp',
        'name_esp',
        'mac_esp',
        'ip_esp',
        'loc_esp',
        'status_esp',
        'news_esp',
        'log_time',
        'created_at',
        'updated_at',
        'sensor_id',
        'sensor_code',
        'sensor_name',
        'val_A',
        'val_B',
        'val_C',
        'val_D',
        'val_E',
        'val_F',
        'val_G',
        'val_H',
        'sensor_timestamp',
        'act_id',
        'act_code',
        'act_name',
        'act_val_A',
        'act_val_B',
        'act_val_C',
        'act_val_D',
        'act_timestamp',
    ];

    /**
     * Format legacy yang lebih lama juga tetap didukung.
     */
    protected array $flatColumnsLegacy = [
        'id',
        'id_esp',
        'name_esp',
        'mac_esp',
        'ip_esp',
        'loc_esp',
        'log_time',
        'created_at',
        'updated_at',
        'sensor_id',
        'sensor_code',
        'sensor_name',
        'val_A',
        'val_B',
        'val_C',
        'val_D',
        'val_E',
        'val_F',
        'val_G',
        'val_H',
        'sensor_timestamp',
        'act_id',
        'act_code',
        'act_name',
        'act_val_A',
        'act_val_B',
        'act_val_C',
        'act_val_D',
        'act_timestamp',
        'status',
        'news',
        'status_timestamp',
    ];

    public function import(string $path): void
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException('File CSV tidak bisa dibaca.');
        }

        try {
            DB::transaction(function () use ($handle) {
                $header = null;
                $rowNumber = 0;

                while (($row = fgetcsv($handle)) !== false) {
                    if ($this->isEmptyRow($row)) {
                        continue;
                    }

                    $rowNumber++;
                    $row = $this->removeBom($row);

                    if ($header === null) {
                        $header = $this->normalizeHeader($row);
                        $this->assertValidHeader($header);
                        continue;
                    }

                    $data = $this->rowToAssoc($header, $row);
                    $this->saveFlatRow($data, $rowNumber);
                }
            });
        } finally {
            fclose($handle);
        }
    }

    protected function assertValidHeader(array $header): void
    {
        $validHeaders = [
            $this->flatColumnsCurrent,
            $this->flatColumnsCompatDeviceStatus,
            $this->flatColumnsLegacy,
        ];

        foreach ($validHeaders as $validHeader) {
            if ($header === $validHeader) {
                return;
            }
        }

        throw new RuntimeException(
            'Header CSV tidak sesuai. Gunakan format terbaru: ' . implode(',', $this->flatColumnsCurrent)
        );
    }

    protected function saveFlatRow(array $data, int $csvRowNumber): void
    {
        $idEsp = $this->clean($data['id_esp'] ?? null);

        if ($idEsp === null) {
            throw new RuntimeException("Baris CSV {$csvRowNumber}: id_esp wajib diisi.");
        }

        $this->saveDevice($idEsp, $data);
        $this->saveStatus($idEsp, $data);
        $this->saveSensor($idEsp, $data);
        $this->saveActuator($idEsp, $data);
    }

    protected function saveDevice(string $idEsp, array $data): void
    {
        $existing = DB::table('device_esp')
            ->where('id_esp', $idEsp)
            ->exists();

        $values = [
            'name_esp' => $this->clean($data['name_esp'] ?? null),
            'mac_esp' => $this->clean($data['mac_esp'] ?? null),
            'ip_esp' => $this->clean($data['ip_esp'] ?? null),
            'loc_esp' => $this->clean($data['loc_esp'] ?? null),
            'timestamp' => $this->resolveDeviceTimestamp($data),
            'updated_at' => $this->resolveDeviceUpdatedAt($data) ?? now(),
        ];

        if ($values['name_esp'] === null) {
            throw new RuntimeException("Device {$idEsp}: name_esp wajib diisi.");
        }

        if ($values['mac_esp'] === null) {
            throw new RuntimeException("Device {$idEsp}: mac_esp wajib diisi.");
        }

        if ($existing) {
            DB::table('device_esp')
                ->where('id_esp', $idEsp)
                ->update($values);

            return;
        }

        DB::table('device_esp')->insert(array_merge([
            'id_esp' => $idEsp,
            'created_at' => $this->resolveDeviceCreatedAt($data) ?? now(),
        ], $values));
    }

    protected function saveStatus(string $idEsp, array $data): void
    {
        $status = $this->resolveStatus($data);
        $news = $this->resolveNews($data);
        $timestamp = $this->resolveStatusTimestamp($data);
        $createdAt = $this->resolveStatusCreatedAt($data);
        $updatedAt = $this->resolveStatusUpdatedAt($data) ?? now();

        if ($status === null && $news === null && $timestamp === null) {
            return;
        }

        if ($status === null) {
            throw new RuntimeException("Status {$idEsp}: status_esp wajib diisi jika record status di-import.");
        }

        $query = DB::table('status_news')
            ->where('id_esp', $idEsp);

        if ($timestamp !== null) {
            $exists = (clone $query)
                ->where('timestamp', $timestamp)
                ->exists();

            if ($exists) {
                $query
                    ->where('timestamp', $timestamp)
                    ->update([
                        'status_esp' => $status,
                        'news_esp' => $news,
                        'updated_at' => $updatedAt,
                    ]);

                return;
            }
        }

        $latest = (clone $query)
            ->orderByDesc('timestamp')
            ->orderByDesc('id')
            ->first();

        if ($latest && $timestamp === null) {
            DB::table('status_news')
                ->where('id', $latest->id)
                ->update([
                    'status_esp' => $status,
                    'news_esp' => $news,
                    'updated_at' => $updatedAt,
                ]);

            return;
        }

        DB::table('status_news')->insert([
            'id_esp' => $idEsp,
            'status_esp' => $status,
            'news_esp' => $news,
            'timestamp' => $timestamp,
            'created_at' => $createdAt ?? now(),
            'updated_at' => $updatedAt,
        ]);
    }

    protected function saveSensor(string $idEsp, array $data): void
    {
        $sensorCode = $this->clean($data['sensor_code'] ?? null);

        if ($sensorCode === null) {
            return;
        }

        DB::table('device_sensor')->updateOrInsert(
            [
                'id_esp' => $idEsp,
                'id_sensor' => $sensorCode,
            ],
            [
                'name_sensor' => $this->clean($data['sensor_name'] ?? null) ?? $sensorCode,
                'val_A' => $this->clean($data['val_A'] ?? null),
                'val_B' => $this->clean($data['val_B'] ?? null),
                'val_C' => $this->clean($data['val_C'] ?? null),
                'val_D' => $this->clean($data['val_D'] ?? null),
                'val_E' => $this->clean($data['val_E'] ?? null),
                'val_F' => $this->clean($data['val_F'] ?? null),
                'val_G' => $this->clean($data['val_G'] ?? null),
                'val_H' => $this->clean($data['val_H'] ?? null),
                'timestamp' => $this->clean($data['sensor_timestamp'] ?? null),
                'updated_at' => now(),
            ]
        );
    }

    protected function saveActuator(string $idEsp, array $data): void
    {
        $actCode = $this->clean($data['act_code'] ?? null);

        if ($actCode === null) {
            return;
        }

        DB::table('device_act')->updateOrInsert(
            [
                'id_esp' => $idEsp,
                'id_act' => $actCode,
            ],
            [
                'name_act' => $this->clean($data['act_name'] ?? null) ?? $actCode,
                'val_A' => $this->clean($data['act_val_A'] ?? null),
                'val_B' => $this->clean($data['act_val_B'] ?? null),
                'val_C' => $this->clean($data['act_val_C'] ?? null),
                'val_D' => $this->clean($data['act_val_D'] ?? null),
                'timestamp' => $this->clean($data['act_timestamp'] ?? null),
                'updated_at' => now(),
            ]
        );
    }

    protected function resolveDeviceTimestamp(array $data): ?string
    {
        return $this->clean($data['device_timestamp'] ?? $data['log_time'] ?? null);
    }

    protected function resolveDeviceCreatedAt(array $data): ?string
    {
        return $this->clean($data['device_created_at'] ?? $data['created_at'] ?? null);
    }

    protected function resolveDeviceUpdatedAt(array $data): ?string
    {
        return $this->clean($data['device_updated_at'] ?? $data['updated_at'] ?? null);
    }

    protected function resolveStatus(array $data): ?string
    {
        return $this->clean($data['status_esp'] ?? $data['status'] ?? null);
    }

    protected function resolveNews(array $data): ?string
    {
        return $this->clean($data['news_esp'] ?? $data['news'] ?? null);
    }

    protected function resolveStatusTimestamp(array $data): ?string
    {
        return $this->clean($data['status_timestamp'] ?? $data['log_time'] ?? null);
    }

    protected function resolveStatusCreatedAt(array $data): ?string
    {
        return $this->clean($data['status_created_at'] ?? null);
    }

    protected function resolveStatusUpdatedAt(array $data): ?string
    {
        return $this->clean($data['status_updated_at'] ?? $data['updated_at'] ?? null);
    }

    protected function rowToAssoc(array $header, array $row): array
    {
        $row = array_pad($row, count($header), null);
        $row = array_slice($row, 0, count($header));

        return array_combine($header, $row) ?: [];
    }

    protected function normalizeHeader(array $row): array
    {
        return array_map(
            fn ($value) => trim((string) $value),
            $row
        );
    }

    protected function clean(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function isEmptyRow(array $row): bool
    {
        return count(array_filter($row, fn ($value) => $value !== null && trim((string) $value) !== '')) === 0;
    }

    protected function removeBom(array $row): array
    {
        $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) ($row[0] ?? ''));

        return $row;
    }
}