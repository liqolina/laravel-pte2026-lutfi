<?php

namespace App\Http\Controllers;

use App\Exports\DeviceActExport;
use App\Exports\DeviceEspExport;
use App\Exports\DeviceSensorExport;
use App\Exports\FullDeviceExport;
use App\Imports\FullDeviceImport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DeviceExportImportController extends Controller
{
    public function exportDeviceEspCsv(): StreamedResponse
    {
        return $this->downloadSingleCsv(
            'export_device_esp.csv',
            DeviceEspExport::table(),
            DeviceEspExport::columns()
        );
    }

    public function exportDeviceSensorCsv(): StreamedResponse
    {
        return $this->downloadSingleCsv(
            'export_device_sensor.csv',
            DeviceSensorExport::table(),
            DeviceSensorExport::columns()
        );
    }

    public function exportDeviceActCsv(): StreamedResponse
    {
        return $this->downloadSingleCsv(
            'export_device_act.csv',
            DeviceActExport::table(),
            DeviceActExport::columns()
        );
    }

    public function exportFullCsv(): StreamedResponse
    {
        return FullDeviceExport::downloadCsv('full_export_device.csv');
    }

    public function exportDeviceEspPdf()
    {
        $pdf = Pdf::loadHTML(
            FullDeviceExport::htmlSingle(
                DeviceEspExport::title(),
                DeviceEspExport::table(),
                DeviceEspExport::columns()
            )
        )->setPaper('a4', 'landscape');

        return $pdf->download('export_device_esp.pdf');
    }

    public function exportDeviceSensorPdf()
    {
        $pdf = Pdf::loadHTML(
            FullDeviceExport::htmlSingle(
                DeviceSensorExport::title(),
                DeviceSensorExport::table(),
                DeviceSensorExport::columns()
            )
        )->setPaper('a4', 'landscape');

        return $pdf->download('export_device_sensor.pdf');
    }

    public function exportDeviceActPdf()
    {
        $pdf = Pdf::loadHTML(
            FullDeviceExport::htmlSingle(
                DeviceActExport::title(),
                DeviceActExport::table(),
                DeviceActExport::columns()
            )
        )->setPaper('a4', 'landscape');

        return $pdf->download('export_device_act.pdf');
    }

    public function exportFullPdf()
    {
        $pdf = Pdf::loadHTML(
            FullDeviceExport::htmlFull()
        )->setPaper('a4', 'landscape');

        return $pdf->download('full_export_device.pdf');
    }

    public function importFullCsv(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        try {
            (new FullDeviceImport())->import($request->file('file'));

            return back()->with('success', 'Import full CSV berhasil.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Import gagal: '.$e->getMessage());
        }
    }

    private function downloadSingleCsv(string $filename, string $table, array $columns): StreamedResponse
    {
        return response()->streamDownload(function () use ($table, $columns) {
            $output = fopen('php://output', 'w');

            fputcsv($output, $columns);

            DB::table($table)
                ->select($columns)
                ->orderBy('id')
                ->chunk(500, function ($rows) use ($output, $columns) {
                    foreach ($rows as $row) {
                        $data = [];

                        foreach ($columns as $column) {
                            $data[] = $row->{$column} ?? null;
                        }

                        fputcsv($output, $data);
                    }
                });

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}