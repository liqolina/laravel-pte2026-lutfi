<?php

use Livewire\Component;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public string $search = '';
    public string $selectedLoc = '';

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

    public function render()
    {
        return $this->view([
            'locations' => $this->getLocations(),
            'devices'   => $this->getDeviceEspRows(),
        ]);
    }
};
?>

<div wire:poll.5s>
    <x-card title="Database | Device ESP" shadow separator>

        {{-- FILTER --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

            <div class="md:col-span-2">
                <label class="text-sm font-semibold mb-2 block">
                    Cari Device
                </label>
                <input
                    type="text"
                    wire:model.live.debounce.500ms="search"
                    placeholder="Cari ID ESP, nama, MAC, IP, atau lokasi..."
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

        </div>

        {{-- TABLE --}}
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
                    @forelse ($devices as $index => $device)
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

                            <td class="whitespace-nowrap">
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
</div>