<?php

namespace App\Exports;

class DeviceSensorExport
{
    public static function table(): string
    {
        return 'device_sensor';
    }

    public static function title(): string
    {
        return 'Export Device Sensor';
    }

    public static function columns(): array
    {
        return [
            'id',
            'id_device',
            'id_sensor',
            'name_sensor',
            'val_A',
            'val_B',
            'val_C',
            'val_D',
            'val_E',
            'val_F',
            'val_G',
            'val_h',
            'timestamp',
        ];
    }
}