<?php

namespace App\Exports;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DeviceDataExport
{
    /**
     * Kolom export mengikuti schema terbaru.
     * Status dipisah ke tabel status_news.
     */
    protected array $columns = [
        'device_esp' => [
            'id', 'id_esp', 'name_esp', 'mac_esp', 'ip_esp', 'loc_esp',
            'timestamp', 'created_at', 'updated_at',
        ],
        'device_sensor' => [
            'id', 'id_esp', 'id_sensor', 'name_sensor',
            'val_A', 'val_B', 'val_C', 'val_D',
            'val_E', 'val_F', 'val_G', 'val_H',
            'timestamp', 'created_at', 'updated_at',
        ],
        'device_act' => [
            'id', 'id_esp', 'id_act', 'name_act',
            'val_A', 'val_B', 'val_C', 'val_D',
            'timestamp', 'created_at', 'updated_at',
        ],
        'status_news' => [
            'id', 'id_esp', 'status_esp', 'news_esp',
            'timestamp', 'created_at', 'updated_at',
        ],
    ];

    /**
     * Header FULL CSV untuk schema terbaru.
     * Device tetap dari device_esp, status/news dari status_news terbaru per device.
     */
    protected array $flatColumns = [
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

    public function getColumns(): array
    {
        return array_merge($this->columns, [
            'full_export' => $this->flatColumns,
        ]);
    }

    protected function getDeviceCode(?int $deviceId = null): ?string
    {
        if ($deviceId === null) {
            return null;
        }

        return DB::table('device_esp')
            ->where('id', $deviceId)
            ->value('id_esp');
    }

    protected function emptyQuery(string $table)
    {
        return DB::table($table)->whereRaw('1 = 0');
    }

    protected function getTableQuery(string $table, ?int $deviceId = null)
    {
        if (!array_key_exists($table, $this->columns)) {
            throw new \InvalidArgumentException("Table {$table} tidak valid.");
        }

        $deviceCode = $this->getDeviceCode($deviceId);

        if ($deviceId !== null && $deviceCode === null && $table !== 'device_esp') {
            return $this->emptyQuery($table);
        }

        return match ($table) {
            'device_esp' => DB::table('device_esp')
                ->when($deviceId !== null, fn ($q) => $q->where('id', $deviceId))
                ->orderBy('id'),

            'status_news' => DB::table('status_news')
                ->when($deviceCode !== null, fn ($q) => $q->where('id_esp', $deviceCode))
                ->orderBy('id'),

            default => DB::table($table)
                ->when($deviceCode !== null, fn ($q) => $q->where('id_esp', $deviceCode))
                ->orderBy('id'),
        };
    }

    public function getTableData(string $table, ?int $deviceId = null): Collection
    {
        return $this->getTableQuery($table, $deviceId)->get();
    }

    public function getFullData(?int $deviceId = null): array
    {
        return [
            'device_esp'    => $this->getTableData('device_esp', $deviceId),
            'device_sensor' => $this->getTableData('device_sensor', $deviceId),
            'device_act'    => $this->getTableData('device_act', $deviceId),
            'status_news'   => $this->getTableData('status_news', $deviceId),
        ];
    }

    /**
     * Membuat data flat berdasarkan schema terbaru.
     *
     * Catatan:
     * - id adalah nomor baris flat.
     * - status_esp/news_esp diambil dari status_news terbaru per device.
     * - sensor dan actuator disusun sejajar berdasarkan urutan id per id_esp.
     */
    public function getFullFlatData(?int $deviceId = null): Collection
    {
        $devices = DB::table('device_esp')
            ->when($deviceId !== null, fn ($q) => $q->where('id', $deviceId))
            ->orderBy('id')
            ->get();

        $rows = [];
        $rowNumber = 1;

        foreach ($devices as $device) {
            $idEsp = $device->id_esp;

            $latestStatus = DB::table('status_news')
                ->where('id_esp', $idEsp)
                ->orderByDesc('timestamp')
                ->orderByDesc('id')
                ->first();

            $sensors = DB::table('device_sensor')
                ->where('id_esp', $idEsp)
                ->orderBy('id')
                ->get()
                ->values();

            $acts = DB::table('device_act')
                ->where('id_esp', $idEsp)
                ->orderBy('id')
                ->get()
                ->values();

            $maxRows = max($sensors->count(), $acts->count(), 1);

            for ($index = 0; $index < $maxRows; $index++) {
                $rows[] = (object) $this->buildFlatRow(
                    $rowNumber++,
                    (array) $device,
                    $latestStatus ? (array) $latestStatus : [],
                    $sensors->get($index) ? (array) $sensors->get($index) : [],
                    $acts->get($index) ? (array) $acts->get($index) : [],
                );
            }
        }

        return collect($rows);
    }

    protected function buildFlatRow(
        int $rowNumber,
        array $device,
        array $status,
        array $sensor,
        array $act
    ): array {
        return [
            'id' => $rowNumber,
            'device_id' => $device['id'] ?? null,
            'id_esp' => $device['id_esp'] ?? null,
            'name_esp' => $device['name_esp'] ?? null,
            'mac_esp' => $device['mac_esp'] ?? null,
            'ip_esp' => $device['ip_esp'] ?? null,
            'loc_esp' => $device['loc_esp'] ?? null,
            'device_timestamp' => $device['timestamp'] ?? null,
            'device_created_at' => $device['created_at'] ?? null,
            'device_updated_at' => $device['updated_at'] ?? null,

            'status_id' => $status['id'] ?? null,
            'status_esp' => $status['status_esp'] ?? null,
            'news_esp' => $status['news_esp'] ?? null,
            'status_timestamp' => $status['timestamp'] ?? null,
            'status_created_at' => $status['created_at'] ?? null,
            'status_updated_at' => $status['updated_at'] ?? null,

            'sensor_id' => $sensor['id'] ?? null,
            'sensor_code' => $sensor['id_sensor'] ?? null,
            'sensor_name' => $sensor['name_sensor'] ?? null,
            'val_A' => $sensor['val_A'] ?? null,
            'val_B' => $sensor['val_B'] ?? null,
            'val_C' => $sensor['val_C'] ?? null,
            'val_D' => $sensor['val_D'] ?? null,
            'val_E' => $sensor['val_E'] ?? null,
            'val_F' => $sensor['val_F'] ?? null,
            'val_G' => $sensor['val_G'] ?? null,
            'val_H' => $sensor['val_H'] ?? null,
            'sensor_timestamp' => $sensor['timestamp'] ?? null,

            'act_id' => $act['id'] ?? null,
            'act_code' => $act['id_act'] ?? null,
            'act_name' => $act['name_act'] ?? null,
            'act_val_A' => $act['val_A'] ?? null,
            'act_val_B' => $act['val_B'] ?? null,
            'act_val_C' => $act['val_C'] ?? null,
            'act_val_D' => $act['val_D'] ?? null,
            'act_timestamp' => $act['timestamp'] ?? null,
        ];
    }

    public function downloadSingleCsv(string $table, ?int $deviceId = null): StreamedResponse
    {
        $rows = $this->getTableData($table, $deviceId);
        $columns = $this->columns[$table];

        return response()->streamDownload(function () use ($rows, $columns) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, $columns);

            foreach ($rows as $row) {
                $item = (array) $row;

                fputcsv($handle, array_map(
                    fn ($column) => $item[$column] ?? null,
                    $columns
                ));
            }

            fclose($handle);
        }, $this->buildFilename($table, 'csv', $deviceId), [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function downloadFullCsv(?int $deviceId = null): StreamedResponse
    {
        $rows = $this->getFullFlatData($deviceId);
        $columns = $this->flatColumns;

        return response()->streamDownload(function () use ($rows, $columns) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, $columns);

            foreach ($rows as $row) {
                $item = (array) $row;

                fputcsv($handle, array_map(
                    fn ($column) => $item[$column] ?? null,
                    $columns
                ));
            }

            fclose($handle);
        }, $this->buildFilename('full-export-flat', 'csv', $deviceId), [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function downloadSinglePdf(string $table, ?int $deviceId = null)
    {
        $tables = [
            $table => $this->getTableData($table, $deviceId),
        ];

        $pdf = Pdf::loadView('exports.devices-pdf', [
            'title'   => strtoupper($table),
            'tables'  => $tables,
            'columns' => $this->getColumns(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($this->buildFilename($table, 'pdf', $deviceId));
    }

    public function downloadFullPdf(?int $deviceId = null)
    {
        $pdf = Pdf::loadView('exports.devices-pdf', [
            'title'   => 'FULL EXPORT DEVICE FLAT',
            'tables'  => [
                'full_export' => $this->getFullFlatData($deviceId),
            ],
            'columns' => $this->getColumns(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($this->buildFilename('full-export-flat', 'pdf', $deviceId));
    }

    protected function buildFilename(string $prefix, string $extension, ?int $deviceId = null): string
    {
        $devicePart = $deviceId ? "-device-{$deviceId}" : '-all';
        $timePart = now()->format('Ymd_His');

        return "{$prefix}{$devicePart}_{$timePart}.{$extension}";
    }
}