<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExportImportController;
use App\Http\Controllers\MqttQueueController;

Route::livewire('/', 'pages::auth.login')->name('login');
Route::livewire('/dashboard', 'pages::dashboard.idx')->name('dashboard');
Route::livewire('/addClient','pages::addClient.idx')->name('addClient');
Route::livewire('/client','pages::client.idx')->name('client');

Route::livewire('/addHardware','pages::hardwareSystem.addHardware.idx')->name('addHardware');
Route::livewire('/listHardware','pages::hardwareSystem.listHardware.idx')->name('listHardware');
Route::livewire('/statusHardware','pages::hardwareSystem.statusHardware.idx')->name('statusHardware');

Route::livewire('/dataActuator','pages::logDatabase.dataActuator.idx')->name('dataActuator');
Route::livewire('/dataEsp','pages::logDatabase.dataEsp.idx')->name('dataEsp');
Route::livewire('/dataSensor','pages::logDatabase.dataSensor.idx')->name('dataSensor');
Route::livewire('/exportimport','pages::logDatabase.exportimport.idx')->name('exportimport');
Route::livewire('/logData','pages::logDatabase.logData.idx')->name('logData');

Route::post('/mqtt/queue', [MqttQueueController::class, 'store']);

Route::prefix('export-import')
    ->name('export-import.')
    ->group(function () {
        Route::get('/device-esp/csv', [ExportImportController::class, 'exportDeviceEspCsv'])
            ->name('device-esp.csv');

        Route::get('/device-sensor/csv', [ExportImportController::class, 'exportDeviceSensorCsv'])
            ->name('device-sensor.csv');

        Route::get('/device-act/csv', [ExportImportController::class, 'exportDeviceActCsv'])
            ->name('device-act.csv');

        Route::get('/full/csv', [ExportImportController::class, 'exportFullCsv'])
            ->name('full.csv');

        Route::get('/device-esp/pdf', [ExportImportController::class, 'exportDeviceEspPdf'])
            ->name('device-esp.pdf');

        Route::get('/device-sensor/pdf', [ExportImportController::class, 'exportDeviceSensorPdf'])
            ->name('device-sensor.pdf');

        Route::get('/device-act/pdf', [ExportImportController::class, 'exportDeviceActPdf'])
            ->name('device-act.pdf');

        Route::get('/full/pdf', [ExportImportController::class, 'exportFullPdf'])
            ->name('full.pdf');
    });