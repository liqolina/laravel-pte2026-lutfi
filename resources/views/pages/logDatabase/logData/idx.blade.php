<?php

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $selectedLoc = '';
    public string $selectedDevice = '';
    public string $selectedSensor = '';
    public string $selectedAct = '';

    public int|string $devicePerPage = 10;
    public int|string $sensorPerPage = 10;
    public int|string $actPerPage = 10;

    protected function resetAllPages(): void
    {
        $this->resetPage('devicePage');
        $this->resetPage('sensorPage');
        $this->resetPage('actPage');
    }

    public function updatingSearch(): void
    {
        $this->resetAllPages();
    }

    public function updatingSelectedDevice(): void
    {
        $this->selectedSensor = '';
        $this->selectedAct = '';
        $this->resetAllPages();
    }

    public function updatingSelectedSensor(): void
    {
        $this->resetAllPages();
    }

    public function updatingSelectedAct(): void
    {
        $this->resetAllPages();
    }

    public function updatingSelectedLoc(): void
    {
        $this->resetAllPages();
    }

    public function updatingDevicePerPage(): void
    {
        $this->resetPage('devicePage');
    }

    public function updatingSensorPerPage(): void
    {
        $this->resetPage('sensorPage');
    }

    public function updatingActPerPage(): void
    {
        $this->resetPage('actPage');
    }

    public function getDeviceOptions()
    {
        return DB::table('device_esp')
            ->select('id_esp', 'name_esp', 'loc_esp')
            ->when($this->selectedLoc !== '', function ($query) {
                $query->where('loc_esp', $this->selectedLoc);
            })
            ->orderBy('name_esp')
            ->get();
    }

    public function getSensorOptions()
    {
        return DB::table('device_sensor as ds')
            ->join('device_esp as d', 'd.id_esp', '=', 'ds.id_esp')
            ->select('ds.id_sensor', 'ds.name_sensor')
            ->when($this->selectedDevice !== '', function ($query) {
                $query->where('ds.id_esp', $this->selectedDevice);
            })
            ->when($this->selectedLoc !== '', function ($query) {
                $query->where('d.loc_esp', $this->selectedLoc);
            })
            ->distinct()
            ->orderBy('ds.name_sensor')
            ->orderBy('ds.id_sensor')
            ->get();
    }

    public function getActOptions()
    {
        return DB::table('device_act as da')
            ->join('device_esp as d', 'd.id_esp', '=', 'da.id_esp')
            ->select('da.id_act', 'da.name_act')
            ->when($this->selectedDevice !== '', function ($query) {
                $query->where('da.id_esp', $this->selectedDevice);
            })
            ->when($this->selectedLoc !== '', function ($query) {
                $query->where('d.loc_esp', $this->selectedLoc);
            })
            ->distinct()
            ->orderBy('da.name_act')
            ->orderBy('da.id_act')
            ->get();
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

    public function getDeviceEspRows()
    {
        $latestStatus = DB::table('status_news')
            ->select('id_esp', DB::raw('MAX(id) as latest_id'))
            ->groupBy('id_esp');

        return DB::table('device_esp as d')
            ->leftJoinSub($latestStatus, 'ls', function ($join) {
                $join->on('ls.id_esp', '=', 'd.id_esp');
            })
            ->leftJoin('status_news as sn', 'sn.id', '=', 'ls.latest_id')
            ->select(
                'd.id',
                'd.id_esp',
                'd.name_esp',
                'd.mac_esp',
                'd.ip_esp',
                'd.loc_esp',
                'd.timestamp as device_timestamp',
                'sn.status_esp',
                'sn.news_esp',
                'sn.timestamp as status_timestamp'
            )
            ->when($this->selectedDevice !== '', function ($query) {
                $query->where('d.id_esp', $this->selectedDevice);
            })
            ->when($this->selectedSensor !== '', function ($query) {
                $query->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('device_sensor as ds')
                        ->whereColumn('ds.id_esp', 'd.id_esp')
                        ->where('ds.id_sensor', $this->selectedSensor);
                });
            })
            ->when($this->selectedAct !== '', function ($query) {
                $query->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('device_act as da')
                        ->whereColumn('da.id_esp', 'd.id_esp')
                        ->where('da.id_act', $this->selectedAct);
                });
            })
            ->when($this->selectedLoc !== '', function ($query) {
                $query->where('d.loc_esp', $this->selectedLoc);
            })
            ->when($this->search !== '', function ($query) {
                $search = '%' . $this->search . '%';

                $query->where(function ($q) use ($search) {
                    $q->where('d.id_esp', 'like', $search)
                        ->orWhere('d.name_esp', 'like', $search)
                        ->orWhere('d.mac_esp', 'like', $search)
                        ->orWhere('d.ip_esp', 'like', $search)
                        ->orWhere('d.loc_esp', 'like', $search)
                        ->orWhere('sn.status_esp', 'like', $search)
                        ->orWhere('sn.news_esp', 'like', $search)
                        ->orWhereExists(function ($sub) use ($search) {
                            $sub->select(DB::raw(1))
                                ->from('device_sensor as ds')
                                ->whereColumn('ds.id_esp', 'd.id_esp')
                                ->where(function ($sensorQ) use ($search) {
                                    $sensorQ->where('ds.id_sensor', 'like', $search)
                                        ->orWhere('ds.name_sensor', 'like', $search);
                                });
                        })
                        ->orWhereExists(function ($sub) use ($search) {
                            $sub->select(DB::raw(1))
                                ->from('device_act as da')
                                ->whereColumn('da.id_esp', 'd.id_esp')
                                ->where(function ($actQ) use ($search) {
                                    $actQ->where('da.id_act', 'like', $search)
                                        ->orWhere('da.name_act', 'like', $search);
                                });
                        });
                });
            })
            ->orderBy('d.name_esp')
            ->paginate((int) $this->devicePerPage, ['*'], 'devicePage');
    }

    public function getSensors()
    {
        return DB::table('device_sensor as ds')
            ->join('device_esp as d', 'd.id_esp', '=', 'ds.id_esp')
            ->select('ds.*', 'd.name_esp', 'd.loc_esp')
            ->when($this->selectedDevice !== '', function ($query) {
                $query->where('ds.id_esp', $this->selectedDevice);
            })
            ->when($this->selectedSensor !== '', function ($query) {
                $query->where('ds.id_sensor', $this->selectedSensor);
            })
            ->when($this->selectedAct !== '', function ($query) {
                $query->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('device_act as da')
                        ->whereColumn('da.id_esp', 'ds.id_esp')
                        ->where('da.id_act', $this->selectedAct);
                });
            })
            ->when($this->selectedLoc !== '', function ($query) {
                $query->where('d.loc_esp', $this->selectedLoc);
            })
            ->when($this->search !== '', function ($query) {
                $search = '%' . $this->search . '%';

                $query->where(function ($q) use ($search) {
                    $q->where('ds.id_sensor', 'like', $search)
                        ->orWhere('ds.name_sensor', 'like', $search)
                        ->orWhere('ds.id_esp', 'like', $search)
                        ->orWhere('d.name_esp', 'like', $search)
                        ->orWhere('d.loc_esp', 'like', $search);
                });
            })
            ->orderByDesc('ds.timestamp')
            ->paginate((int) $this->sensorPerPage, ['*'], 'sensorPage');
    }

    public function getActs()
    {
        return DB::table('device_act as da')
            ->join('device_esp as d', 'd.id_esp', '=', 'da.id_esp')
            ->select('da.*', 'd.name_esp', 'd.loc_esp')
            ->when($this->selectedDevice !== '', function ($query) {
                $query->where('da.id_esp', $this->selectedDevice);
            })
            ->when($this->selectedSensor !== '', function ($query) {
                $query->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('device_sensor as ds')
                        ->whereColumn('ds.id_esp', 'da.id_esp')
                        ->where('ds.id_sensor', $this->selectedSensor);
                });
            })
            ->when($this->selectedAct !== '', function ($query) {
                $query->where('da.id_act', $this->selectedAct);
            })
            ->when($this->selectedLoc !== '', function ($query) {
                $query->where('d.loc_esp', $this->selectedLoc);
            })
            ->when($this->search !== '', function ($query) {
                $search = '%' . $this->search . '%';

                $query->where(function ($q) use ($search) {
                    $q->where('da.id_act', 'like', $search)
                        ->orWhere('da.name_act', 'like', $search)
                        ->orWhere('da.id_esp', 'like', $search)
                        ->orWhere('d.name_esp', 'like', $search)
                        ->orWhere('d.loc_esp', 'like', $search);
                });
            })
            ->orderByDesc('da.timestamp')
            ->paginate((int) $this->actPerPage, ['*'], 'actPage');
    }

    public function render()
    {
        return $this->view([
            'deviceOptions' => $this->getDeviceOptions(),
            'sensorOptions' => $this->getSensorOptions(),
            'actOptions'    => $this->getActOptions(),
            'locations'     => $this->getLocations(),
            'deviceRows'    => $this->getDeviceEspRows(),
            'sensors'       => $this->getSensors(),
            'acts'          => $this->getActs(),
        ]);
    }
};
?>

<div wire:poll.5s class="space-y-6">

    {{-- FILTER UTAMA --}}
    <x-card title="Database | Filter Data" shadow separator>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">

            <div>
                <label class="text-sm font-semibold mb-2 block">
                    Search
                </label>
                <input
                    type="text"
                    wire:model.live.debounce.500ms="search"
                    placeholder="Cari device, sensor, actuator, ID, MAC, IP, atau lokasi..."
                    class="input input-bordered w-full"
                >
            </div>

            <div>
                <label class="text-sm font-semibold mb-2 block">
                    List Device
                </label>
                <select wire:model.live="selectedDevice" class="select select-bordered w-full">
                    <option value="">Semua Device</option>

                    @foreach ($deviceOptions as $dev)
                        <option value="{{ $dev->id_esp }}">
                            {{ $dev->name_esp }} - {{ $dev->id_esp }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-semibold mb-2 block">
                    List Sensor
                </label>
                <select wire:model.live="selectedSensor" class="select select-bordered w-full">
                    <option value="">Semua Sensor</option>

                    @foreach ($sensorOptions as $sensorOption)
                        <option value="{{ $sensorOption->id_sensor }}">
                            {{ $sensorOption->name_sensor }} - {{ $sensorOption->id_sensor }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-semibold mb-2 block">
                    List Actuator
                </label>
                <select wire:model.live="selectedAct" class="select select-bordered w-full">
                    <option value="">Semua Actuator</option>

                    @foreach ($actOptions as $actOption)
                        <option value="{{ $actOption->id_act }}">
                            {{ $actOption->name_act }} - {{ $actOption->id_act }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-semibold mb-2 block">
                    List Location
                </label>
                <select wire:model.live="selectedLoc" class="select select-bordered w-full">
                    <option value="">Semua Lokasi</option>

                    @foreach ($locations as $location)
                        <option value="{{ $location->loc_esp }}">
                            {{ $location->loc_esp }}
                        </option>
                    @endforeach
                </select>
            </div>

        </div>
    </x-card>


    {{-- DEVICE ESP --}}
    <x-card title="Database | Device ESP" shadow separator>

        <div class="flex justify-end mb-4">
            <div class="w-full md:w-40">
                <label class="text-sm font-semibold mb-2 block">
                    Baris Table
                </label>
                <select wire:model.live="devicePerPage" class="select select-bordered w-full">
                    <option value="10">10</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-base-300 bg-base-100">
            <table class="table table-zebra table-sm">
                <thead class="bg-base-200">
                    <tr>
                        <th>No</th>
                        <th>Device</th>
                        <th>ID ESP</th>
                        <th>MAC Address</th>
                        <th>IP Address</th>
                        <th>Lokasi</th>
                        <th>Status</th>
                        <th>News</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($deviceRows as $index => $device)
                        <tr>
                            <td class="font-semibold">
                                {{ ($deviceRows->firstItem() ?? 1) + $index }}
                            </td>

                            <td>
                                <div class="font-bold">
                                    {{ $device->name_esp }}
                                </div>
                                <div class="text-xs opacity-60">
                                    DB ID: {{ $device->id }}
                                </div>
                            </td>

                            <td>
                                <span class="badge badge-outline">
                                    {{ $device->id_esp }}
                                </span>
                            </td>

                            <td class="whitespace-nowrap">
                                {{ $device->mac_esp }}
                            </td>

                            <td class="whitespace-nowrap">
                                {{ $device->ip_esp }}
                            </td>

                            <td>
                                {{ $device->loc_esp }}
                            </td>

                            <td>
                                @php
                                    $isOnline = $device->status_timestamp
                                        ? \Illuminate\Support\Carbon::parse($device->status_timestamp)->gte(now()->subSeconds(10))
                                        : false;
                                @endphp

                                @if ($device->status_timestamp)
                                    <span class="badge {{ $isOnline ? 'badge-success' : 'badge-error' }}">
                                        {{ $isOnline ? 'ONLINE' : 'OFFLINE' }}
                                    </span>
                                @else
                                    <span class="badge badge-ghost">
                                        No Status
                                    </span>
                                @endif
                            </td>

                            <td class="min-w-56">
                                {{ $device->news_esp ?? '-' }}
                            </td>

                            <td class="whitespace-nowrap text-xs opacity-70">
                                {{ $device->status_timestamp ?? $device->device_timestamp ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-8 opacity-60">
                                Data device ESP belum tersedia
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $deviceRows->links() }}
        </div>

    </x-card>


    {{-- DEVICE SENSOR --}}
    <x-card title="Database | Device Sensor" shadow separator>

        <div class="flex justify-end mb-4">
            <div class="w-full md:w-40">
                <label class="text-sm font-semibold mb-2 block">
                    Baris Table
                </label>
                <select wire:model.live="sensorPerPage" class="select select-bordered w-full">
                    <option value="10">10</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-base-300 bg-base-100">
            <table class="table table-zebra table-sm">
                <thead class="bg-base-200">
                    <tr>
                        <th>No</th>
                        <th>Device</th>
                        <th>Lokasi</th>
                        <th>Sensor</th>
                        <th>ID Sensor</th>
                        <th>Voltage (A)</th>
                        <th>Current (B)</th>
                        <th>Power (C)</th>
                        <th>Energy (D)</th>
                        <th>Freq (E)</th>
                        <th>PF (F)</th>
                        <th>Temp (G)</th>
                        <th>Extra</th>
                        <th>Time</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($sensors as $i => $sensor)
                        <tr>
                            <td>{{ ($sensors->firstItem() ?? 1) + $i }}</td>

                            <td class="font-bold">
                                {{ $sensor->name_esp }}
                            </td>

                            <td>
                                {{ $sensor->loc_esp }}
                            </td>

                            <td>
                                {{ $sensor->name_sensor }}
                            </td>

                            <td>
                                <span class="badge badge-outline">
                                    {{ $sensor->id_sensor }}
                                </span>
                            </td>

                            <td>{{ $sensor->val_A ?? '-' }}</td>
                            <td>{{ $sensor->val_B ?? '-' }}</td>
                            <td>{{ $sensor->val_C ?? '-' }}</td>
                            <td>{{ $sensor->val_D ?? '-' }}</td>
                            <td>{{ $sensor->val_E ?? '-' }}</td>
                            <td>{{ $sensor->val_F ?? '-' }}</td>
                            <td>{{ $sensor->val_G ?? '-' }}</td>
                            <td>{{ $sensor->val_H ?? '-' }}</td>

                            <td class="whitespace-nowrap text-xs opacity-70">
                                {{ $sensor->timestamp }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="text-center py-6 opacity-60">
                                Tidak ada data sensor
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $sensors->links() }}
        </div>

    </x-card>


    {{-- DEVICE ACTUATOR --}}
    <x-card title="Database | Device Actuator" shadow separator>

        <div class="flex justify-end mb-4">
            <div class="w-full md:w-40">
                <label class="text-sm font-semibold mb-2 block">
                    Baris Table
                </label>
                <select wire:model.live="actPerPage" class="select select-bordered w-full">
                    <option value="10">10</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-base-300 bg-base-100">
            <table class="table table-zebra table-sm">
                <thead class="bg-base-200">
                    <tr>
                        <th>No</th>
                        <th>Device</th>
                        <th>Lokasi</th>
                        <th>Actuator</th>
                        <th>ID Act</th>
                        <th>Status (A)</th>
                        <th>Value B</th>
                        <th>Value C</th>
                        <th>Value D</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($acts as $i => $act)
                        <tr>
                            <td>{{ ($acts->firstItem() ?? 1) + $i }}</td>

                            <td class="font-bold">
                                {{ $act->name_esp }}
                            </td>

                            <td>
                                {{ $act->loc_esp }}
                            </td>

                            <td>
                                {{ $act->name_act }}
                            </td>

                            <td>
                                <span class="badge badge-outline">
                                    {{ $act->id_act }}
                                </span>
                            </td>

                            <td>
                                @if ($act->val_A == 1)
                                    <span class="badge badge-success">ON</span>
                                @else
                                    <span class="badge badge-error">OFF</span>
                                @endif
                            </td>

                            <td>{{ $act->val_B ?? '-' }}</td>
                            <td>{{ $act->val_C ?? '-' }}</td>
                            <td>{{ $act->val_D ?? '-' }}</td>

                            <td class="whitespace-nowrap text-xs opacity-70">
                                {{ $act->timestamp }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-6 opacity-60">
                                Tidak ada data actuator
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $acts->links() }}
        </div>

    </x-card>

</div>