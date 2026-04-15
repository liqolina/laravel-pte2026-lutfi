<?php

namespace App\Exports;

class DeviceActExport
{
    public static function table(): string
    {
        return 'device_act';
    }

    public static function title(): string
    {
        return 'Export Device Act';
    }

    public static function columns(): array
    {
        return [
            'id',
            'id_device',
            'id_act',
            'name_act',
            'val_A',
            'val_B',
            'val_C',
            'val_D',
            'timestamp',
        ];
    }
}