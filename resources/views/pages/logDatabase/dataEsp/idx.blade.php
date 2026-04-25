<?php

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $selectedDevice = '';
    public string $selectedLoc = '';
    public int $perPage = 10;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedDevice(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedLoc(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
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
            ->select('id_esp', 'name_esp')
            ->distinct()
            ->orderBy('name_esp')
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
            ->paginate($this->perPage);
    }

    public function render()
    {
        return $this->view([
            'locations' => $this->getLocations(),
            'deviceList' => $this->getDevices(),
            'devices' => $this->getDeviceEspRows(),
        ]);
    }
};
?>

<div wire:poll.5s>
    <x-card title="Database | Device ESP" shadow separator>

        {{-- FILTER --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

            <div>
                <label class="text-sm font-semibold mb-2 block">
                    Search
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
                    List Device
                </label>
                <select wire:model.live="selectedDevice" class="select select-bordered w-full">
                    <option value="">Semua Device</option>

                    @foreach ($deviceList as $deviceOption)
                        <option value="{{ $deviceOption->id_esp }}">
                            {{ $deviceOption->name_esp }} ({{ $deviceOption->id_esp }})
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

        <div class="flex justify-end mb-4">
            <div class="w-full md:w-40">
                <label class="text-sm font-semibold mb-2 block">
                    Baris Tabel
                </label>
                <select wire:model.live="perPage" class="select select-bordered w-full">
                    <option value="10">10</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
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
                        <th>Status</th>
                        <th>News</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($devices as $index => $device)
                        <tr>
                            <td class="font-semibold">
                                {{ ($devices->firstItem() ?? 1) + $index }}
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

                            <td class="whitespace-nowrap">
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
            {{ $devices->links() }}
        </div>

    </x-card>
</div>