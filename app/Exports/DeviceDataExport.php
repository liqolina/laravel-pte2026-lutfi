<?php

namespace App\Exports;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DeviceDataExport
{
    protected array $columns = [
        'device_esp' => [
            'id', 'id_esp', 'name_esp', 'mac_esp', 'ip_esp', 'loc_esp', 'log_time',
        ],
        'device_sensor' => [
            'id', 'id_device', 'id_sensor', 'name_sensor',
            'val_A', 'val_B', 'val_C', 'val_D',
            'val_E', 'val_F', 'val_G', 'val_h',
            'timestamp',
        ],
        'device_act' => [
            'id', 'id_device', 'id_act', 'name_act',
            'val_A', 'val_B', 'val_C', 'val_D',
            'timestamp',
        ],
        'status_news' => [
            'id', 'id_device', 'status_device', 'news_device', 'timestamp',
        ],
    ];

    protected array $combinedColumns = [
        'table_name',
        'id',
        'id_device',
        'id_esp',
        'name_esp',
        'mac_esp',
        'ip_esp',
        'loc_esp',
        'log_time',
        'id_sensor',
        'name_sensor',
        'id_act',
        'name_act',
        'val_A',
        'val_B',
        'val_C',
        'val_D',
        'val_E',
        'val_F',
        'val_G',
        'val_h',
        'status_device',
        'news_device',
        'timestamp',
    ];

    public function getColumns(): array
    {
        return array_merge($this->columns, [
            'full_export' => $this->combinedColumns,
        ]);
    }

    protected function getTableQuery(string $table, ?int $deviceId = null)
    {
        return match ($table) {
            'device_esp' => DB::table('device_esp')
                ->when($deviceId, fn ($q) => $q->where('id', $deviceId))
                ->orderBy('id'),

            'device_sensor' => DB::table('device_sensor')
                ->when($deviceId, fn ($q) => $q->where('id_device', $deviceId))
                ->orderBy('id'),

            'device_act' => DB::table('device_act')
                ->when($deviceId, fn ($q) => $q->where('id_device', $deviceId))
                ->orderBy('id'),

            'status_news' => DB::table('status_news')
                ->when($deviceId, fn ($q) => $q->where('id_device', $deviceId))
                ->orderBy('id'),

            default => throw new \InvalidArgumentException("Table {$table} tidak valid."),
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

    public function getFullCombinedData(?int $deviceId = null): Collection
    {
        $rows = [];

        foreach ($this->getFullData($deviceId) as $table => $tableRows) {
            foreach ($tableRows as $row) {
                $rows[] = (object) $this->buildCombinedRow($table, (array) $row);
            }
        }

        return collect($rows);
    }

    protected function buildCombinedRow(string $table, array $row): array
    {
        $combinedRow = array_fill_keys($this->combinedColumns, null);
        $combinedRow['table_name'] = $table;

        foreach ($this->columns[$table] as $column) {
            $combinedRow[$column] = $row[$column] ?? null;
        }

        return $combinedRow;
    }

    public function downloadSingleCsv(string $table, ?int $deviceId = null): StreamedResponse
    {
        $rows = $this->getTableData($table, $deviceId);
        $columns = $this->columns[$table];

        return response()->streamDownload(function () use ($rows, $columns) {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8 supaya aman dibuka di Excel
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
        $rows = $this->getFullCombinedData($deviceId);
        $columns = $this->combinedColumns;

        return response()->streamDownload(function () use ($rows, $columns) {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8 supaya aman dibuka di Excel
            fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Full export memakai satu header saja, tanpa penanda #TABLE dan tanpa baris kosong antar tabel.
            fputcsv($handle, $columns);

            foreach ($rows as $row) {
                $item = (array) $row;

                fputcsv($handle, array_map(
                    fn ($column) => $item[$column] ?? null,
                    $columns
                ));
            }

            fclose($handle);
        }, $this->buildFilename('full-export', 'csv', $deviceId), [
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
            'title'   => 'FULL EXPORT DEVICE',
            'tables'  => [
                'full_export' => $this->getFullCombinedData($deviceId),
            ],
            'columns' => $this->getColumns(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($this->buildFilename('full-export', 'pdf', $deviceId));
    }

    protected function buildFilename(string $prefix, string $extension, ?int $deviceId = null): string
    {
        $devicePart = $deviceId ? "-device-{$deviceId}" : '-all';
        $timePart = now()->format('Ymd_His');

        return "{$prefix}{$devicePart}_{$timePart}.{$extension}";
    }
}