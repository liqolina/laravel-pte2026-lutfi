<?php

namespace App\Http\Controllers;

use App\Exports\DeviceDataExport;
use Illuminate\Http\Request;

class ExportImportController extends Controller
{
    public function __construct(
        protected DeviceDataExport $exportService
    ) {}

    protected function getDeviceId(Request $request): ?int
    {
        if (!$request->filled('device')) {
            return null;
        }

        return (int) $request->get('device');
    }

    public function exportDeviceEspCsv(Request $request)
    {
        return $this->exportService->downloadSingleCsv('device_esp', $this->getDeviceId($request));
    }

    public function exportDeviceSensorCsv(Request $request)
    {
        return $this->exportService->downloadSingleCsv('device_sensor', $this->getDeviceId($request));
    }

    public function exportDeviceActCsv(Request $request)
    {
        return $this->exportService->downloadSingleCsv('device_act', $this->getDeviceId($request));
    }

    public function exportStatusNewsCsv(Request $request)
    {
        return $this->exportService->downloadSingleCsv('status_news', $this->getDeviceId($request));
    }

    public function exportFullCsv(Request $request)
    {
        return $this->exportService->downloadFullCsv($this->getDeviceId($request));
    }

    public function exportDeviceEspPdf(Request $request)
    {
        return $this->exportService->downloadSinglePdf('device_esp', $this->getDeviceId($request));
    }

    public function exportDeviceSensorPdf(Request $request)
    {
        return $this->exportService->downloadSinglePdf('device_sensor', $this->getDeviceId($request));
    }

    public function exportDeviceActPdf(Request $request)
    {
        return $this->exportService->downloadSinglePdf('device_act', $this->getDeviceId($request));
    }

    public function exportStatusNewsPdf(Request $request)
    {
        return $this->exportService->downloadSinglePdf('status_news', $this->getDeviceId($request));
    }

    public function exportFullPdf(Request $request)
    {
        return $this->exportService->downloadFullPdf($this->getDeviceId($request));
    }
}