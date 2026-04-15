<?php

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $selectedDevice = '';
    public string $selectedSensor = '';
    public string $selectedLoc = '';
    public int|string $perPage = 10;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSelectedDevice()
    {
        $this->selectedSensor = '';
        $this->resetPage();
    }

    public function updatingSelectedSensor()
    {
        $this->resetPage();
    }

    public function updatingSelectedLoc()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function getDevices()
    {
        return DB::table('device_esp')
            ->orderBy('name_esp')
            ->get();
    }

    public function getSensorsList()
    {
        return DB::table('device_sensor as s')
            ->join('device_esp as d', 'd.id_esp', '=', 's.id_esp')
            ->when($this->selectedDevice !== '', function ($q) {
                $q->where('s.id_esp', $this->selectedDevice);
            })
            ->when($this->selectedLoc !== '', function ($q) {
                $q->where('d.loc_esp', $this->selectedLoc);
            })
            ->select('s.id_sensor', 's.name_sensor')
            ->distinct()
            ->orderBy('s.name_sensor')
            ->orderBy('s.id_sensor')
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

    public function getSensors()
    {
        return DB::table('device_sensor as s')
            ->join('device_esp as d', 'd.id_esp', '=', 's.id_esp')
            ->when($this->selectedDevice !== '', function ($q) {
                $q->where('s.id_esp', $this->selectedDevice);
            })
            ->when($this->selectedSensor !== '', function ($q) {
                $q->where('s.id_sensor', $this->selectedSensor);
            })
            ->when($this->selectedLoc !== '', function ($q) {
                $q->where('d.loc_esp', $this->selectedLoc);
            })
            ->when($this->search !== '', function ($q) {
                $q->where(function ($sub) {
                    $sub->where('d.name_esp', 'like', '%' . $this->search . '%')
                        ->orWhere('d.id_esp', 'like', '%' . $this->search . '%')
                        ->orWhere('d.loc_esp', 'like', '%' . $this->search . '%')
                        ->orWhere('s.name_sensor', 'like', '%' . $this->search . '%')
                        ->orWhere('s.id_sensor', 'like', '%' . $this->search . '%');
                });
            })
            ->orderByDesc('s.timestamp')
            ->select('s.*', 'd.name_esp', 'd.loc_esp')
            ->paginate((int) $this->perPage);
    }

    public function render()
    {
        return $this->view([
            'devices' => $this->getDevices(),
            'sensorOptions' => $this->getSensorsList(),
            'locations' => $this->getLocations(),
            'sensors' => $this->getSensors(),
        ]);
    }
};
?>

<div wire:poll.5s>
    <x-card title="Database | Device Sensor" shadow separator>

        {{-- FILTER --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div>
                <label class="text-sm font-semibold mb-2 block">Search</label>
                <input
                    type="text"
                    wire:model.live.debounce.500ms="search"
                    placeholder="Cari device, sensor, ID, atau lokasi..."
                    class="input input-bordered w-full"
                >
            </div>

            <div>
                <label class="text-sm font-semibold mb-2 block">List Device</label>
                <select wire:model.live="selectedDevice" class="select select-bordered w-full">
                    <option value="">Semua Device</option>

                    @foreach ($devices as $dev)
                        <option value="{{ $dev->id_esp }}">
                            {{ $dev->name_esp }} - {{ $dev->id_esp }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-semibold mb-2 block">List Sensor</label>
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
                <label class="text-sm font-semibold mb-2 block">List Location</label>
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
                <label class="text-sm font-semibold mb-2 block">Baris Table</label>
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
                        <th>Sensor</th>
                        <th>ID Sensor</th>
                        <th>Location</th>
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
                            <td>{{ $sensors->firstItem() + $i }}</td>

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

                            <td>
                                {{ $sensor->loc_esp ?? '-' }}
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
</div>