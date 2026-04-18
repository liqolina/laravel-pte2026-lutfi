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

    public function import(string $path): void
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException('File CSV tidak bisa dibaca.');
        }

        try {
            DB::transaction(function () use ($handle) {
                $table = null;
                $header = [];
                $expectHeader = false;

                while (($row = fgetcsv($handle)) !== false) {
                    // skip baris kosong
                    if (!$row || count(array_filter($row, fn ($v) => $v !== null && $v !== '')) === 0) {
                        continue;
                    }

                    // hapus BOM UTF-8
                    $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) ($row[0] ?? ''));

                    // penanda tabel
                    if (($row[0] ?? null) === '#TABLE') {
                        $candidateTable = $row[1] ?? null;

                        if (!in_array($candidateTable, $this->allowedTables, true)) {
                            throw new RuntimeException("Tabel {$candidateTable} tidak diizinkan.");
                        }

                        $table = $candidateTable;
                        $header = [];
                        $expectHeader = true;
                        continue;
                    }

                    // header tabel
                    if ($expectHeader) {
                        $header = $row;
                        $expectHeader = false;

                        if ($header !== $this->columns[$table]) {
                            throw new RuntimeException("Header tabel {$table} tidak sesuai format full export.");
                        }

                        continue;
                    }

                    if (!$table || empty($header)) {
                        continue;
                    }

                    // samakan panjang row dengan header
                    $row = array_pad($row, count($header), null);
                    $row = array_slice($row, 0, count($header));

                    $data = array_combine($header, $row);

                    if (!$data || !array_key_exists('id', $data) || $data['id'] === null || $data['id'] === '') {
                        continue;
                    }

                    // hanya kolom yang dikenal
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
            });
        } finally {
            fclose($handle);
        }
    }
}