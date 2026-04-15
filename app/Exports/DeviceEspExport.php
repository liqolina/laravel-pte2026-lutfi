<?php

namespace App\Exports;

class DeviceEspExport
{
    public static function table(): string
    {
        return 'device_esp';
    }

    public static function title(): string
    {
        return 'Export Device ESP';
    }

    public static function columns(): array
    {
        return [
            'id',
            'id_esp',
            'name_esp',
            'mac_esp',
            'ip_esp',
            'loc_esp',
            'log_time',
        ];
    }
}