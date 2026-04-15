<?php

namespace App\Imports;

use App\Exports\DeviceActExport;
use App\Exports\DeviceEspExport;
use App\Exports\DeviceSensorExport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FullDeviceImport
{
    private array $sections;

    public function __construct()
    {
        $this->sections = [
            DeviceEspExport::table() => DeviceEspExport::columns(),
            DeviceSensorExport::table() => DeviceSensorExport::columns(),
            DeviceActExport::table() => DeviceActExport::columns(),
        ];
    }

    public function import(UploadedFile $file): void
    {
        DB::transaction(function () use ($file) {
            $handle = fopen($file->getRealPath(), 'r');

            if (!$handle) {
                throw new RuntimeException('File CSV tidak bisa dibuka.');
            }

            $currentTable = null;
            $currentHeaders = [];
            $waitingHeader = false;
            $foundTables = [];

            try {
                while (($row = fgetcsv($handle)) !== false) {
                    if ($this->isEmptyRow($row)) {
                        continue;
                    }

                    if (($row[0] ?? null) === '#TABLE') {
                        $currentTable = $row[1] ?? null;

                        if (!array_key_exists($currentTable, $this->sections)) {
                            throw new RuntimeException('Table tidak valid pada CSV: '.$currentTable);
                        }

                        $waitingHeader = true;
                        $currentHeaders = [];
                        $foundTables[] = $currentTable;

                        continue;
                    }

                    if ($waitingHeader) {
                        $currentHeaders = $row;
                        $expectedHeaders = $this->sections[$currentTable];

                        if ($currentHeaders !== $expectedHeaders) {
                            throw new RuntimeException('Header CSV tidak sesuai untuk table: '.$currentTable);
                        }

                        $waitingHeader = false;

                        continue;
                    }

                    if (!$currentTable || empty($currentHeaders)) {
                        continue;
                    }

                    $row = array_slice(
                        array_pad($row, count($currentHeaders), null),
                        0,
                        count($currentHeaders)
                    );

                    $data = array_combine($currentHeaders, $row);
                    $data = $this->normalizeData($data, $this->sections[$currentTable]);

                    if (empty($data['id'])) {
                        continue;
                    }

                    DB::table($currentTable)->updateOrInsert(
                        ['id' => $data['id']],
                        $data
                    );
                }
            } finally {
                fclose($handle);
            }

            $missingTables = array_diff(array_keys($this->sections), array_unique($foundTables));

            if (!empty($missingTables)) {
                throw new RuntimeException(
                    'CSV bukan hasil Full Export. Table berikut tidak ditemukan: '.implode(', ', $missingTables)
                );
            }
        });
    }

    private function normalizeData(array $data, array $columns): array
    {
        $clean = [];

        foreach ($columns as $column) {
            $value = $data[$column] ?? null;

            if (is_string($value)) {
                $value = trim($value);
            }

            $clean[$column] = $value === '' ? null : $value;
        }

        return $clean;
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}