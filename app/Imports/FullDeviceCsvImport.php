<?php

namespace App\Imports;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class FullDeviceCsvImport
{
    protected array $allowedTables = [
        'device_esp',
        'device_sensor',
        'device_act',
        'status_news',
    ];

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

    public function import(string $path): void
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException('File CSV tidak bisa dibaca.');
        }

        try {
            DB::transaction(function () use ($handle) {
                $format = null;
                $table = null;
                $header = [];
                $expectHeader = false;

                while (($row = fgetcsv($handle)) !== false) {
                    if ($this->isEmptyRow($row)) {
                        continue;
                    }

                    $row = $this->removeBom($row);

                    if ($format === null) {
                        if (($row[0] ?? null) === '#TABLE') {
                            $format = 'sectioned';
                            [$table, $header, $expectHeader] = $this->startSectionedTable($row);
                            continue;
                        }

                        if ($row === $this->combinedColumns) {
                            $format = 'combined';
                            $header = $row;
                            continue;
                        }

                        $detectedTable = $this->detectSingleTable($row);

                        if ($detectedTable !== null) {
                            $format = 'single';
                            $table = $detectedTable;
                            $header = $row;
                            continue;
                        }

                        throw new RuntimeException('Header CSV tidak sesuai. Gunakan file hasil Full Export CSV gabungan terbaru.');
                    }

                    if ($format === 'combined') {
                        $this->importCombinedRow($header, $row);
                        continue;
                    }

                    if ($format === 'single') {
                        $this->importTableRow($table, $header, $row);
                        continue;
                    }

                    // Kompatibilitas lama: masih bisa membaca CSV lama yang memakai #TABLE.
                    if (($row[0] ?? null) === '#TABLE') {
                        [$table, $header, $expectHeader] = $this->startSectionedTable($row);
                        continue;
                    }

                    if ($expectHeader) {
                        $header = $row;
                        $expectHeader = false;

                        if ($header !== $this->columns[$table]) {
                            throw new RuntimeException("Header tabel {$table} tidak sesuai format export.");
                        }

                        continue;
                    }

                    if (!$table || empty($header)) {
                        continue;
                    }

                    $this->importTableRow($table, $header, $row);
                }
            });
        } finally {
            fclose($handle);
        }
    }

    protected function startSectionedTable(array $row): array
    {
        $table = $row[1] ?? null;

        if (!in_array($table, $this->allowedTables, true)) {
            throw new RuntimeException("Tabel {$table} tidak diizinkan.");
        }

        return [$table, [], true];
    }

    protected function detectSingleTable(array $header): ?string
    {
        foreach ($this->columns as $table => $columns) {
            if ($header === $columns) {
                return $table;
            }
        }

        return null;
    }

    protected function importCombinedRow(array $header, array $row): void
    {
        $data = $this->rowToAssoc($header, $row);
        $table = $data['table_name'] ?? null;

        if (!in_array($table, $this->allowedTables, true)) {
            throw new RuntimeException("Tabel {$table} tidak diizinkan.");
        }

        $this->saveRow($table, $data);
    }

    protected function importTableRow(string $table, array $header, array $row): void
    {
        $data = $this->rowToAssoc($header, $row);
        $this->saveRow($table, $data);
    }

    protected function rowToAssoc(array $header, array $row): array
    {
        $row = array_pad($row, count($header), null);
        $row = array_slice($row, 0, count($header));

        return array_combine($header, $row) ?: [];
    }

    protected function saveRow(string $table, array $data): void
    {
        if (!array_key_exists('id', $data) || $data['id'] === null || $data['id'] === '') {
            return;
        }

        $filtered = [];

        foreach ($this->columns[$table] as $column) {
            $value = $data[$column] ?? null;
            $filtered[$column] = ($value === '') ? null : $value;
        }

        DB::table($table)->updateOrInsert(
            ['id' => $filtered['id']],
            $filtered
        );
    }

    protected function isEmptyRow(array $row): bool
    {
        return count(array_filter($row, fn ($value) => $value !== null && $value !== '')) === 0;
    }

    protected function removeBom(array $row): array
    {
        $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) ($row[0] ?? ''));

        return $row;
    }
}