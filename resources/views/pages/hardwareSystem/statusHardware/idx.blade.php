<?php

use Livewire\Component;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public $selectedLoc = '';
    public $selectedDevice = '';

    /* =========================================================
     | SENSOR CONFIG
     ========================================================= */
    public $sensorUnits = [
        'val_A' => ['label' => 'Voltage', 'unit' => 'V',  'icon' => 'o-bolt'],
        'val_B' => ['label' => 'Current', 'unit' => 'A',  'icon' => 'o-arrow-trending-up'],
        'val_C' => ['label' => 'Power',   'unit' => 'W',  'icon' => 'o-bolt'],
        'val_D' => ['label' => 'Energy',  'unit' => 'Wh', 'icon' => 'o-chart-bar'],
        'val_E' => ['label' => 'Freq',    'unit' => 'Hz', 'icon' => 'o-signal'],
        'val_F' => ['label' => 'PF',      'unit' => 'PF', 'icon' => 'o-adjustments-horizontal'],
        'val_G' => ['label' => 'Temp',    'unit' => '°C', 'icon' => 'o-fire'],
        'val_h' => ['label' => 'Extra',   'unit' => '',   'icon' => 'o-cpu-chip'],
    ];

    /* =========================================================
     | ACTUATOR CONFIG
     ========================================================= */
    public $actUnits = [
        'val_A' => ['label' => 'Status', 'unit' => '', 'icon' => 'o-power'],
        'val_B' => ['label' => 'Value B','unit' => '', 'icon' => 'o-cog-6-tooth'],
        'val_C' => ['label' => 'Value C','unit' => '', 'icon' => 'o-bolt'],
        'val_D' => ['label' => 'Value D','unit' => '', 'icon' => 'o-adjustments-horizontal'],
    ];

    /* =========================================================
     | MOUNT
     ========================================================= */
    public function mount()
    {
        $this->selectedLoc = DB::table('device_esp')
            ->whereNotNull('loc_esp')
            ->orderBy('loc_esp')
            ->value('loc_esp') ?? '';

        $this->selectedDevice = DB::table('device_esp')
            ->when($this->selectedLoc, fn ($q) => $q->where('loc_esp', $this->selectedLoc))
            ->orderBy('name_esp')
            ->value('id') ?? '';
    }

    /* =========================================================
     | EVENTS
     ========================================================= */
    public function updatedSelectedLoc()
    {
        $this->selectedDevice = DB::table('device_esp')
            ->where('loc_esp', $this->selectedLoc)
            ->orderBy('name_esp')
            ->value('id') ?? '';
    }

    /* =========================================================
     | DATA FETCH
     ========================================================= */
    public function getLocations()
    {
        return DB::table('device_esp')
            ->select('loc_esp')
            ->whereNotNull('loc_esp')
            ->distinct()
            ->orderBy('loc_esp')
            ->get();
    }

    public function getDevices()
    {
        return DB::table('device_esp')
            ->when($this->selectedLoc, fn ($q) => $q->where('loc_esp', $this->selectedLoc))
            ->orderBy('name_esp')
            ->get();
    }

    public function getSelectedDeviceData()
    {
        if (!$this->selectedDevice) return null;

        return DB::table('device_esp')
            ->where('id', $this->selectedDevice)
            ->first();
    }

    public function getLatestSensors()
    {
        if (!$this->selectedDevice) return collect();

        return DB::table('device_sensor')
            ->where('id_device', $this->selectedDevice)
            ->orderByDesc('timestamp')
            ->get()
            ->unique('id_sensor')
            ->values();
    }

    public function getLatestActs()
    {
        if (!$this->selectedDevice) return collect();

        return DB::table('device_act')
            ->where('id_device', $this->selectedDevice)
            ->orderByDesc('timestamp')
            ->get()
            ->unique('id_act')
            ->values();
    }

    public function getStatusNews()
    {
        if (!$this->selectedDevice) return null;

        return DB::table('status_news')
            ->where('id_device', $this->selectedDevice)
            ->orderByDesc('timestamp')
            ->first();
    }

    /* =========================================================
     | RENDER
     ========================================================= */
    public function render()
    {
        return $this->view([
            'locations'   => $this->getLocations(),
            'devices'     => $this->getDevices(),
            'device'      => $this->getSelectedDeviceData(),
            'sensors'     => $this->getLatestSensors(),
            'acts'        => $this->getLatestActs(),
            'statusNews'  => $this->getStatusNews(),
        ]);
    }
};
?>

<div wire:poll.5s>
    <x-card title="Admin | Device Sensor Dashboard" shadow separator>

        {{-- ================= FILTER ================= --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="text-sm font-semibold mb-2 block">Lokasi</label>
                <select wire:model.live="selectedLoc" class="select select-bordered w-full">
                    <option value="">Semua Lokasi</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->loc_esp }}">
                            {{ $location->loc_esp }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-semibold mb-2 block">Device</label>
                <select wire:model.live="selectedDevice" class="select select-bordered w-full">
                    <option value="">Pilih Device</option>
                    @foreach ($devices as $dev)
                        <option value="{{ $dev->id }}">
                            {{ $dev->name_esp }} - {{ $dev->id_esp }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- ================= DEVICE INFO ================= --}}
        @if ($device)
            <div class="mb-6 rounded-2xl border border-dashed bg-base-100 p-5">
                <div class="flex flex-col md:flex-row justify-between gap-2">
                    <div>
                        <div class="text-xl font-bold">
                            {{ $device->name_esp }}
                        </div>
                        <div class="text-sm opacity-70">
                            {{ $device->id_esp }} | {{ $device->mac_esp }} | {{ $device->ip_esp }}
                        </div>
                    </div>

                    <div class="text-sm font-semibold opacity-70">
                        Lokasi: {{ $device->loc_esp }}
                    </div>
                </div>
            </div>
        @endif

        {{-- ================= SENSOR ================= --}}
        <div class="mb-10">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold">Sensor</h3>
                <span class="text-xs opacity-60">Live 5s</span>
            </div>

            @if ($sensors->count())
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($sensors as $sensor)
                        <div class="border border-dashed rounded-2xl bg-base-100 p-4 hover:shadow-md transition">

                            <div class="mb-3">
                                <div class="font-bold">{{ $sensor->name_sensor }}</div>
                                <div class="text-xs opacity-60">{{ $sensor->id_sensor }}</div>
                            </div>

                            <div class="space-y-2">
                                @foreach ($sensorUnits as $field => $meta)
                                    @if (!is_null($sensor->{$field}))
                                        <div class="flex items-center justify-between bg-base-200 rounded-xl p-3">
                                            
                                            <div class="flex items-center gap-2">
                                                <x-icon name="{{ $meta['icon'] }}" class="w-5 h-5" />
                                                <span class="text-sm">{{ $meta['label'] }}</span>
                                            </div>

                                            <div class="font-bold text-lg">
                                                {{ number_format((float) $sensor->{$field}, 2) }}
                                            </div>

                                            <div class="text-sm opacity-70 w-10 text-right">
                                                {{ $meta['unit'] }}
                                            </div>

                                        </div>
                                    @endif
                                @endforeach
                            </div>

                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-6 text-center border border-dashed rounded-2xl opacity-60">
                    No sensor data
                </div>
            @endif
        </div>

        {{-- ================= ACTUATOR ================= --}}
        <div class="mb-10">
            <h3 class="text-lg font-bold mb-4">Actuator</h3>

            @if ($acts->count())
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($acts as $act)
                        <div class="border border-dashed rounded-2xl bg-base-100 p-4 hover:shadow-md transition">

                            <div class="mb-3">
                                <div class="font-bold">{{ $act->name_act }}</div>
                                <div class="text-xs opacity-60">{{ $act->id_act }}</div>
                            </div>

                            <div class="space-y-2">
                                @foreach ($actUnits as $field => $meta)
                                    @if (!is_null($act->{$field}))
                                        <div class="flex items-center justify-between bg-base-200 rounded-xl p-3">

                                            <div class="flex items-center gap-2">
                                                <x-icon name="{{ $meta['icon'] }}" class="w-5 h-5" />
                                                <span class="text-sm">{{ $meta['label'] }}</span>
                                            </div>

                                            <div class="font-bold text-lg">
                                                {{ number_format((float) $act->{$field}, 2) }}
                                            </div>

                                            <div class="text-sm opacity-70 w-10 text-right">
                                                {{ $meta['unit'] }}
                                            </div>

                                        </div>
                                    @endif
                                @endforeach
                            </div>

                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-6 text-center border border-dashed rounded-2xl opacity-60">
                    No actuator data
                </div>
            @endif
        </div>

        {{-- ================= STATUS ================= --}}
        <div class="mt-6">
            <h3 class="text-lg font-bold mb-4">Status Device</h3>

            @if ($statusNews)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <div class="border border-dashed rounded-2xl p-5 bg-base-100">
                        <div class="text-sm opacity-60">Status</div>
                        <div class="text-2xl font-bold">
                            {{ $statusNews->status_device }}
                        </div>
                    </div>

                    <div class="border border-dashed rounded-2xl p-5 bg-base-100">
                        <div class="text-sm opacity-60">News</div>
                        <div class="font-semibold">
                            {{ $statusNews->news_device }}
                        </div>
                    </div>

                </div>
            @else
                <div class="p-6 text-center border border-dashed rounded-2xl opacity-60">
                    No status data
                </div>
            @endif
        </div>

    </x-card>
</div>