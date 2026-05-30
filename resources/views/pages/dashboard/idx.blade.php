<?php

use Livewire\Component;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public string $selectedLoc = '';
    public string $selectedDevice = '';

    public array $sensorUnits = [
        'val_A' => ['label' => 'Voltage', 'unit' => 'V'],
        'val_B' => ['label' => 'Current', 'unit' => 'A'],
        'val_C' => ['label' => 'Power', 'unit' => 'W'],
        'val_D' => ['label' => 'Energy', 'unit' => 'Wh'],
        'val_E' => ['label' => 'Frequency', 'unit' => 'Hz'],
        'val_F' => ['label' => 'Power Factor', 'unit' => 'PF'],
        'val_G' => ['label' => 'Temperature', 'unit' => '°C'],
        'val_H' => ['label' => 'Value H', 'unit' => ''],
    ];

    public array $actUnits = [
        'val_A' => ['label' => 'Relay / Output A', 'unit' => ''],
        'val_B' => ['label' => 'Output B', 'unit' => ''],
        'val_C' => ['label' => 'Output C', 'unit' => ''],
        'val_D' => ['label' => 'Output D', 'unit' => ''],
    ];

    public function mount(): void
    {
        $this->selectedLoc = DB::table('device_esp')
            ->whereNotNull('loc_esp')
            ->orderBy('loc_esp')
            ->value('loc_esp') ?? '';

        $this->selectedDevice = DB::table('device_esp')
            ->when($this->selectedLoc !== '', fn ($q) => $q->where('loc_esp', $this->selectedLoc))
            ->orderBy('name_esp')
            ->value('id_esp') ?? '';
    }

    public function updatedSelectedLoc(): void
    {
        $this->selectedDevice = DB::table('device_esp')
            ->when($this->selectedLoc !== '', fn ($q) => $q->where('loc_esp', $this->selectedLoc))
            ->orderBy('name_esp')
            ->value('id_esp') ?? '';
    }

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
            ->when($this->selectedLoc !== '', fn ($q) => $q->where('loc_esp', $this->selectedLoc))
            ->orderBy('name_esp')
            ->get();
    }

    public function getSelectedDeviceData()
    {
        if ($this->selectedDevice === '') {
            return null;
        }

        return DB::table('device_esp')
            ->where('id_esp', $this->selectedDevice)
            ->first();
    }

    public function getLatestSensorRow()
    {
        if ($this->selectedDevice === '') {
            return null;
        }

        return DB::table('device_sensor')
            ->where('id_esp', $this->selectedDevice)
            ->orderByDesc('timestamp')
            ->orderByDesc('id')
            ->first();
    }

    public function getLatestActRow()
    {
        if ($this->selectedDevice === '') {
            return null;
        }

        return DB::table('device_act')
            ->where('id_esp', $this->selectedDevice)
            ->orderByDesc('timestamp')
            ->orderByDesc('id')
            ->first();
    }

    public function getLatestStatusRow()
    {
        if ($this->selectedDevice === '') {
            return null;
        }

        return DB::table('status_news')
            ->where('id_esp', $this->selectedDevice)
            ->orderByDesc('timestamp')
            ->orderByDesc('id')
            ->first();
    }

    public function getDeviceInfoBoxes($device): Collection
    {
        if (!$device) {
            return collect();
        }

        return collect([
            ['label' => 'ID Device', 'value' => $device->id_esp ?: '-'],
            ['label' => 'Device Name', 'value' => $device->name_esp ?: '-'],
            ['label' => 'IP Address', 'value' => $device->ip_esp ?: '-'],
            ['label' => 'MAC Address', 'value' => $device->mac_esp ?: '-'],
            ['label' => 'Location', 'value' => $device->loc_esp ?: '-'],
            ['label' => 'Timestamp', 'value' => $device->timestamp ?: '-'],
        ]);
    }

    public function getSensorCards($sensor): Collection
    {
        if (!$sensor) {
            return collect();
        }

        return collect($this->sensorUnits)
            ->map(function ($meta, $field) use ($sensor) {
                if (!property_exists($sensor, $field) || is_null($sensor->{$field})) {
                    return null;
                }

                return [
                    'label' => $meta['label'],
                    'value' => $sensor->{$field},
                    'unit' => $meta['unit'],
                    'is_text' => !is_numeric($sensor->{$field}),
                ];
            })
            ->filter()
            ->values();
    }

    public function getActCards($act): Collection
    {
        if (!$act) {
            return collect();
        }

        return collect($this->actUnits)
            ->map(function ($meta, $field) use ($act) {
                if (!property_exists($act, $field) || is_null($act->{$field})) {
                    return null;
                }

                $value = $act->{$field};

                if ($field === 'val_A') {
                    $value = (float) $value == 1.0 ? 'ON' : 'OFF';
                }

                return [
                    'label' => $meta['label'],
                    'value' => $value,
                    'unit' => $meta['unit'],
                    'is_text' => !is_numeric($value),
                ];
            })
            ->filter()
            ->values();
    }

    public function render()
    {
        $device = $this->getSelectedDeviceData();
        $sensor = $this->getLatestSensorRow();
        $act = $this->getLatestActRow();
        $statusRow = $this->getLatestStatusRow();

        return $this->view([
            'locations' => $this->getLocations(),
            'devices' => $this->getDevices(),
            'deviceInfoBoxes' => $this->getDeviceInfoBoxes($device),
            'sensorCards' => $this->getSensorCards($sensor),
            'actCards' => $this->getActCards($act),
            'device' => $device,
            'statusRow' => $statusRow,
        ]);
    }
};
?>

@php
    $statusTimestamp = $statusRow->timestamp ?? null;
    $isOnline = $statusTimestamp
        ? \Illuminate\Support\Carbon::parse($statusTimestamp)->gte(now()->subSeconds(10))
        : false;
    $status = $device
        ? ($isOnline ? 'ONLINE' : 'OFFLINE')
        : 'NO DEVICE';
    $lastUpdate = $statusTimestamp ?? $device->timestamp ?? $device->updated_at ?? '-';
@endphp

<div wire:poll.5s>
    <div class="mx-auto space-y-6">

        {{-- HEADER / HERO --}}
        <div class="overflow-hidden rounded-[2rem] bg-gradient-to-br from-primary via-primary to-secondary text-primary-content shadow-xl">
            <div class="relative p-6 md:p-8">
                <div class="absolute -right-16 -top-16 h-48 w-48 rounded-full bg-white/10 blur-2xl"></div>
                <div class="absolute -bottom-20 left-1/3 h-56 w-56 rounded-full bg-white/10 blur-3xl"></div>

            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center">
                <div class="flex-1">
                    <div class="mb-3 inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-2 text-xs font-semibold uppercase tracking-[0.25em] backdrop-blur">
                        <span class="h-2 w-2 rounded-full bg-current"></span>
                        Realtime IoT Monitoring
                    </div>
                    <h1 class="text-3xl font-bold tracking-tight md:text-5xl">Dashboard Device</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-relaxed opacity-85 md:text-base">
                        Informasi perangkat, data sensor, actuator, status, dan news.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:ml-auto lg:min-w-[420px] lg:max-w-[520px]">
                    <div class="rounded-2xl bg-white/15 p-4 backdrop-blur">
                        <div class="text-xs font-semibold uppercase opacity-75">Locations</div>
                        <div class="mt-2 text-3xl font-bold">{{ $locations->count() }}</div>
                    </div>

                    <div class="rounded-2xl bg-white/15 p-4 backdrop-blur">
                        <div class="text-xs font-semibold uppercase opacity-75">Devices</div>
                        <div class="mt-2 text-3xl font-bold">{{ $devices->count() }}</div>
                    </div>

                    <div class="col-span-2 rounded-2xl bg-white/15 p-4 backdrop-blur sm:col-span-1">
                        <div class="text-xs font-semibold uppercase opacity-75">Status</div>
                        <div class="mt-2">
                            <span class="badge border-0 px-4 py-3 font-bold {{ $isOnline ? 'badge-success' : 'badge-error' }}">
                                {{ $status }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </div>

        {{-- FILTER CARD --}}
        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="card-body p-4 md:p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
                    <div class="flex-1">
                        <label class="mb-2 flex items-center gap-2 text-xs font-bold uppercase tracking-wider opacity-70">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-4.438 7-11a7 7 0 1 0-14 0c0 6.562 7 11 7 11Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10.5a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" />
                            </svg>
                            Location
                        </label>
                        <select wire:model.live="selectedLoc" class="select select-bordered w-full rounded-2xl bg-base-200/60">
                            <option value="">Semua Lokasi</option>
                            @foreach ($locations as $location)
                                <option value="{{ $location->loc_esp }}">{{ $location->loc_esp }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex-1">
                        <label class="mb-2 flex items-center gap-2 text-xs font-bold uppercase tracking-wider opacity-70">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 3h6m-7 4h8a2 2 0 0 1 2 2v9a3 3 0 0 1-3 3H9a3 3 0 0 1-3-3V9a2 2 0 0 1 2-2Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 11h6M9 15h3" />
                            </svg>
                            Device
                        </label>
                        <select wire:model.live="selectedDevice" class="select select-bordered w-full rounded-2xl bg-base-200/60">
                            <option value="">Pilih Device</option>
                            @foreach ($devices as $dev)
                                <option value="{{ $dev->id_esp }}">{{ $dev->name_esp }} - {{ $dev->id_esp }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="rounded-2xl border border-base-300 bg-base-200/60 px-4 py-3 lg:min-w-[260px]">
                        <div class="text-xs font-bold uppercase tracking-wider opacity-70">Last Update</div>
                        <div class="mt-1 flex items-center justify-between gap-3">
                            <span class="truncate font-semibold">{{ $lastUpdate }}</span>
                            <span wire:loading.delay class="loading loading-spinner loading-sm"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- MAIN CONTENT --}}
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">

            {{-- LEFT CONTENT --}}
            <div class="space-y-6 xl:col-span-8">

                {{-- SENSOR --}}
                <section class="card border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body p-4 md:p-6">
                        <div class="mb-2 flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-xl font-bold">Sensor Overview</h2>
                                <p class="text-sm opacity-60">Data sensor terakhir berdasarkan device yang dipilih.</p>
                            </div>
                            <div class="badge badge-primary badge-outline">Realtime</div>
                        </div>

                        @if ($sensorCards->count())
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 2xl:grid-cols-4">
                                @foreach ($sensorCards as $card)
                                    <div class="group rounded-3xl border border-base-300 bg-base-200/40 p-5 transition hover:-translate-y-1 hover:bg-base-100 hover:shadow-lg">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <div class="text-xs font-bold uppercase tracking-wider opacity-60">{{ $card['label'] }}</div>
                                                <div class="mt-3 leading-none">
                                                    @if (!empty($card['is_text']))
                                                        <span class="text-3xl font-extrabold">{{ $card['value'] }}</span>
                                                    @else
                                                        <span class="text-3xl font-extrabold">{{ number_format((float) $card['value'], 2) }}</span>
                                                        @if ($card['unit'] !== '')
                                                            <span class="ml-1 text-sm font-bold opacity-60">{{ $card['unit'] }}</span>
                                                        @endif
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="rounded-2xl bg-primary/10 p-3 text-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.5h3l2.25-6 4.5 12L15 13.5h6" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="rounded-3xl border border-dashed border-base-300 bg-base-200/40 px-4 py-12 text-center">
                                <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-base-300/60">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" />
                                    </svg>
                                </div>
                                <div class="font-semibold">No sensor data</div>
                                <div class="text-sm opacity-60">Belum ada data sensor untuk device ini.</div>
                            </div>
                        @endif
                    </div>
                </section>

                {{-- ACTUATOR --}}
                <section class="card border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body p-4 md:p-6">
                        <div class="mb-2 flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-xl font-bold">Actuator Control Status</h2>
                                <p class="text-sm opacity-60">Status actuator terakhir berdasarkan data database.</p>
                            </div>
                            <div class="badge badge-secondary badge-outline">Output</div>
                        </div>

                        @if ($actCards->count())
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 2xl:grid-cols-4">
                                @foreach ($actCards as $card)
                                    <div class="rounded-3xl border border-base-300 bg-base-200/40 p-5 transition hover:-translate-y-1 hover:bg-base-100 hover:shadow-lg">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <div class="text-xs font-bold uppercase tracking-wider opacity-60">{{ $card['label'] }}</div>
                                                <div class="mt-3 leading-none">
                                                    @if (!empty($card['is_text']))
                                                        <span class="badge px-5 py-4 text-lg font-extrabold {{ $card['value'] === 'ON' ? 'badge-success' : 'badge-error' }}">
                                                            {{ $card['value'] }}
                                                        </span>
                                                    @else
                                                        <span class="text-3xl font-extrabold">{{ number_format((float) $card['value'], 2) }}</span>
                                                        @if ($card['unit'] !== '')
                                                            <span class="ml-1 text-sm font-bold opacity-60">{{ $card['unit'] }}</span>
                                                        @endif
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="rounded-2xl bg-secondary/10 p-3 text-secondary">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 14.25A4.5 4.5 0 0 1 12 9.75a4.5 4.5 0 0 1 4.5 4.5M12 3v3m0 12v3M3 12h3m12 0h3M5.64 5.64l2.12 2.12m8.48 8.48 2.12 2.12m0-12.72-2.12 2.12m-8.48 8.48-2.12 2.12" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="rounded-3xl border border-dashed border-base-300 bg-base-200/40 px-4 py-12 text-center">
                                <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-base-300/60">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" />
                                    </svg>
                                </div>
                                <div class="font-semibold">No actuator data</div>
                                <div class="text-sm opacity-60">Belum ada data actuator untuk device ini.</div>
                            </div>
                        @endif
                    </div>
                </section>
            </div>

            {{-- RIGHT SIDEBAR --}}
            <aside class="space-y-6 xl:col-span-4">

                {{-- DEVICE INFORMATION --}}
                <section class="card border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body p-4 md:p-6">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-xl font-bold">Device Information</h2>
                                <p class="text-sm opacity-60">Detail perangkat terpilih.</p>
                            </div>
                            <div class="avatar placeholder">
                                <div class="w-12 rounded-2xl bg-primary text-primary-content">
                                    <span class="text-lg font-bold">{{ strtoupper(substr($device->name_esp ?? 'D', 0, 1)) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="divider my-2"></div>

                        @forelse ($deviceInfoBoxes as $box)
                            <div class="rounded-2xl border border-base-300 bg-base-200/40 px-4 py-3">
                                <div class="text-xs font-bold uppercase tracking-wider opacity-55">{{ $box['label'] }}</div>
                                <div class="mt-1 break-all font-semibold">{{ $box['value'] }}</div>
                            </div>
                        @empty
                            <div class="rounded-3xl border border-dashed border-base-300 bg-base-200/40 px-4 py-10 text-center opacity-70">
                                Device belum dipilih
                            </div>
                        @endforelse
                    </div>
                </section>

                {{-- STATUS AND NEWS --}}
                <section class="card border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body p-4 md:p-6">
                        <h2 class="text-xl font-bold">Status & News</h2>
                        <p class="text-sm opacity-60">Informasi kondisi perangkat dari database.</p>

                        <div class="mt-4 rounded-3xl border border-base-300 bg-base-200/40 p-5">
                            <div class="mb-2 text-xs font-bold uppercase tracking-wider opacity-55">Status</div>
                            <span class="badge px-5 py-4 text-sm font-extrabold {{ $isOnline ? 'badge-success' : 'badge-error' }}">
                                {{ $status }}
                            </span>
                        </div>

                        <div class="rounded-3xl border border-base-300 bg-base-200/40 p-5">
                            <div class="mb-2 text-xs font-bold uppercase tracking-wider opacity-55">News</div>
                            <div class="text-sm font-medium leading-relaxed">
                                {{ $statusRow->news_esp ?? '-' }}
                            </div>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</div>