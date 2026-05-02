<?php

use Livewire\Component;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public string $search = '';
    public string $selectedLoc = '';
    public string $selectedDevice = '';

    public function getDeviceOptions()
    {
        return DB::table('device_esp')
            ->orderBy('name_esp')
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
            ->select('id_device', DB::raw('MAX(id) as latest_id'))
            ->groupBy('id_device');

        $sensorCount = DB::table('device_sensor')
            ->select('id_device', DB::raw('COUNT(DISTINCT id_sensor) as total_sensor'))
            ->groupBy('id_device');

        $actCount = DB::table('device_act')
            ->select('id_device', DB::raw('COUNT(DISTINCT id_act) as total_act'))
            ->groupBy('id_device');

        return DB::table('device_esp as d')
            ->leftJoinSub($latestStatus, 'ls', function ($join) {
                $join->on('d.id', '=', 'ls.id_device');
            })
            ->leftJoin('status_news as s', 's.id', '=', 'ls.latest_id')
            ->leftJoinSub($sensorCount, 'sc', function ($join) {
                $join->on('d.id', '=', 'sc.id_device');
            })
            ->leftJoinSub($actCount, 'ac', function ($join) {
                $join->on('d.id', '=', 'ac.id_device');
            })
            ->select(
                'd.id',
                'd.id_esp',
                'd.name_esp',
                'd.mac_esp',
                'd.ip_esp',
                'd.loc_esp',
                'd.log_time',
                's.status_device',
                's.news_device',
                's.timestamp as status_time',
                DB::raw('COALESCE(sc.total_sensor, 0) as total_sensor'),
                DB::raw('COALESCE(ac.total_act, 0) as total_act')
            )
            ->when($this->selectedLoc !== '', function ($query) {
                $query->where('d.loc_esp', $this->selectedLoc);
            })
            ->when($this->search !== '', function ($query) {
                $query->where(function ($q) {
                    $q->where('d.id_esp', 'like', '%' . $this->search . '%')
                        ->orWhere('d.name_esp', 'like', '%' . $this->search . '%')
                        ->orWhere('d.mac_esp', 'like', '%' . $this->search . '%')
                        ->orWhere('d.ip_esp', 'like', '%' . $this->search . '%')
                        ->orWhere('d.loc_esp', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('d.name_esp')
            ->get();
    }

    public function getSensors()
    {
        return DB::table('device_sensor as s')
            ->join('device_esp as d', 'd.id', '=', 's.id_device')
            ->when($this->selectedDevice !== '', function ($q) {
                $q->where('s.id_device', $this->selectedDevice);
            })
            ->orderByDesc('s.timestamp')
            ->select('s.*', 'd.name_esp')
            ->get();
    }

    public function getActs()
    {
        return DB::table('device_act as a')
            ->join('device_esp as d', 'd.id', '=', 'a.id_device')
            ->when($this->selectedDevice !== '', function ($q) {
                $q->where('a.id_device', $this->selectedDevice);
            })
            ->orderByDesc('a.timestamp')
            ->select('a.*', 'd.name_esp')
            ->get();
    }

    public function render()
    {
        return $this->view([
            'deviceOptions' => $this->getDeviceOptions(),
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
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

            <div>
                <label class="text-sm font-semibold mb-2 block">
                    Cari Device ESP
                </label>
                <input
                    type="text"
                    wire:model.live.debounce.500ms="search"
                    placeholder="Cari ID ESP, nama, MAC, IP, lokasi..."
                    class="input input-bordered w-full"
                >
            </div>

            <div>
                <label class="text-sm font-semibold mb-2 block">
                    Lokasi
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

            <div>
                <label class="text-sm font-semibold mb-2 block">
                    Device Sensor / Actuator
                </label>
                <select wire:model.live="selectedDevice" class="select select-bordered w-full">
                    <option value="">Semua Device</option>

                    @foreach ($deviceOptions as $dev)
                        <option value="{{ $dev->id }}">
                            {{ $dev->name_esp }} - {{ $dev->id_esp }}
                        </option>
                    @endforeach
                </select>
            </div>

        </div>
    </x-card>


    {{-- DEVICE ESP --}}
    <x-card title="Database | Device ESP" shadow separator>

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
                        <th>Sensor</th>
                        <th>Actuator</th>
                        <th>Status</th>
                        <th>News</th>
                        <th>Log Time</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($deviceRows as $index => $device)
                        <tr>
                            <td class="font-semibold">
                                {{ $index + 1 }}
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
                                <span class="badge badge-info badge-outline">
                                    {{ $device->total_sensor }}
                                </span>
                            </td>

                            <td>
                                <span class="badge badge-warning badge-outline">
                                    {{ $device->total_act }}
                                </span>
                            </td>

                            <td>
                                @if ($device->status_device)
                                    <span class="badge badge-success">
                                        {{ $device->status_device }}
                                    </span>
                                @else
                                    <span class="badge badge-ghost">
                                        No Status
                                    </span>
                                @endif
                            </td>

                            <td class="min-w-56">
                                {{ $device->news_device ?? '-' }}
                            </td>

                            <td class="whitespace-nowrap text-xs opacity-70">
                                {{ $device->log_time ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center py-8 opacity-60">
                                Data device ESP belum tersedia
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </x-card>


    {{-- DEVICE SENSOR --}}
    <x-card title="Database | Device Sensor" shadow separator>

        <div class="overflow-x-auto rounded-2xl border border-base-300 bg-base-100">
            <table class="table table-zebra table-sm">
                <thead class="bg-base-200">
                    <tr>
                        <th>No</th>
                        <th>Device</th>
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
                            <td>{{ $i + 1 }}</td>

                            <td class="font-bold">
                                {{ $sensor->name_esp }}
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
                            <td>{{ $sensor->val_h ?? '-' }}</td>

                            <td class="whitespace-nowrap text-xs opacity-70">
                                {{ $sensor->timestamp }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="text-center py-6 opacity-60">
                                Tidak ada data sensor
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </x-card>


    {{-- DEVICE ACTUATOR --}}
    <x-card title="Database | Device Actuator" shadow separator>

        <div class="overflow-x-auto rounded-2xl border border-base-300 bg-base-100">
            <table class="table table-zebra table-sm">
                <thead class="bg-base-200">
                    <tr>
                        <th>No</th>
                        <th>Device</th>
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
                            <td>{{ $i + 1 }}</td>

                            <td class="font-bold">
                                {{ $act->name_esp }}
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
                            <td colspan="9" class="text-center py-6 opacity-60">
                                Tidak ada data actuator
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </x-card>

</div>