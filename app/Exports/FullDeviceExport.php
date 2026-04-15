<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FullDeviceExport
{
    public static function sections(): array
    {
        return [
            DeviceEspExport::table() => [
                'title' => DeviceEspExport::title(),
                'columns' => DeviceEspExport::columns(),
            ],
            DeviceSensorExport::table() => [
                'title' => DeviceSensorExport::title(),
                'columns' => DeviceSensorExport::columns(),
            ],
            DeviceActExport::table() => [
                'title' => DeviceActExport::title(),
                'columns' => DeviceActExport::columns(),
            ],
        ];
    }

    public static function downloadCsv(string $filename = 'full_export_device.csv'): StreamedResponse
    {
        return response()->streamDownload(function () {
            $output = fopen('php://output', 'w');

            foreach (self::sections() as $table => $config) {
                fputcsv($output, ['#TABLE', $table]);
                fputcsv($output, $config['columns']);

                DB::table($table)
                    ->select($config['columns'])
                    ->orderBy('id')
                    ->chunk(500, function ($rows) use ($output, $config) {
                        foreach ($rows as $row) {
                            $data = [];

                            foreach ($config['columns'] as $column) {
                                $data[] = $row->{$column} ?? null;
                            }

                            fputcsv($output, $data);
                        }
                    });

                fputcsv($output, []);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public static function htmlSingle(string $title, string $table, array $columns): string
    {
        return self::document(
            self::tableHtml($title, $table, $columns)
        );
    }

    public static function htmlFull(): string
    {
        $body = '<h1>Full Export Device</h1>';

        foreach (self::sections() as $table => $config) {
            $body .= self::tableHtml($config['title'], $table, $config['columns']);
        }

        return self::document($body);
    }

    private static function document(string $body): string
    {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <style>
                body {
                    font-family: DejaVu Sans, sans-serif;
                    font-size: 10px;
                    color: #111827;
                }

                h1 {
                    font-size: 18px;
                    margin-bottom: 20px;
                }

                h2 {
                    font-size: 14px;
                    margin-top: 22px;
                    margin-bottom: 8px;
                }

                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 18px;
                }

                th {
                    background: #f3f4f6;
                    font-weight: bold;
                }

                th, td {
                    border: 1px solid #d1d5db;
                    padding: 5px;
                    text-align: left;
                    vertical-align: top;
                    word-break: break-word;
                }
            </style>
        </head>
        <body>
            '.$body.'
        </body>
        </html>';
    }

    private static function tableHtml(string $title, string $table, array $columns): string
    {
        $rows = DB::table($table)
            ->select($columns)
            ->orderBy('id')
            ->get();

        $html = '<h2>'.e($title).'</h2>';
        $html .= '<table>';
        $html .= '<thead><tr>';

        foreach ($columns as $column) {
            $html .= '<th>'.e($column).'</th>';
        }

        $html .= '</tr></thead>';
        $html .= '<tbody>';

        if ($rows->isEmpty()) {
            $html .= '<tr><td colspan="'.count($columns).'">Data kosong</td></tr>';
        } else {
            foreach ($rows as $row) {
                $html .= '<tr>';

                foreach ($columns as $column) {
                    $html .= '<td>'.e($row->{$column} ?? '').'</td>';
                }

                $html .= '</tr>';
            }
        }

        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }
}