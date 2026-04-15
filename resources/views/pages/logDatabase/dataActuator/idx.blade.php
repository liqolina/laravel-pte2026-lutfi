<?php

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $selectedDevice = '';
    public string $selectedAct = '';
    public string $selectedLoc = '';
    public int|string $perPage = 10;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSelectedDevice()
    {
        $this->selectedAct = '';
        $this->resetPage();
    }

    public function updatingSelectedAct()
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
            ->select('id_esp', 'name_esp')
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

    public function getActsList()
    {
        return DB::table('device_act as a')
            ->join('device_esp as d', 'd.id_esp', '=', 'a.id_esp')
            ->when($this->selectedDevice !== '', function ($query) {
                $query->where('a.id_esp', $this->selectedDevice);
            })
            ->when($this->selectedLoc !== '', function ($query) {
                $query->where('d.loc_esp', $this->selectedLoc);
            })
            ->select('a.id_act', 'a.name_act')
            ->distinct()
            ->orderBy('a.name_act')
            ->orderBy('a.id_act')
            ->get();
    }

    public function getActs()
    {
        return DB::table('device_act as a')
            ->join('device_esp as d', 'd.id_esp', '=', 'a.id_esp')
            ->when($this->selectedDevice !== '', function ($query) {
                $query->where('a.id_esp', $this->selectedDevice);
            })
            ->when($this->selectedAct !== '', function ($query) {
                $query->where('a.id_act', $this->selectedAct);
            })
            ->when($this->selectedLoc !== '', function ($query) {
                $query->where('d.loc_esp', $this->selectedLoc);
            })
            ->when($this->search !== '', function ($query) {
                $search = '%' . $this->search . '%';

                $query->where(function ($q) use ($search) {
                    $q->where('d.name_esp', 'like', $search)
                        ->orWhere('d.id_esp', 'like', $search)
                        ->orWhere('d.loc_esp', 'like', $search)
                        ->orWhere('a.name_act', 'like', $search)
                        ->orWhere('a.id_act', 'like', $search)
                        ->orWhere('a.val_A', 'like', $search)
                        ->orWhere('a.val_B', 'like', $search)
                        ->orWhere('a.val_C', 'like', $search)
                        ->orWhere('a.val_D', 'like', $search)
                        ->orWhere('a.timestamp', 'like', $search);
                });
            })
            ->orderBy('d.name_esp')
            ->orderByDesc('a.timestamp')
            ->select('a.*', 'd.name_esp', 'd.loc_esp')
            ->paginate((int) $this->perPage);
    }

    public function render()
    {
        return $this->view([
            'deviceList' => $this->getDevices(),
            'actOptions' => $this->getActsList(),
            'locations'  => $this->getLocations(),
            'acts'       => $this->getActs(),
        ]);
    }
};
?>

<div wire:poll.5s>
    <x-card title="Database | Device Actuator" shadow separator>

        {{-- FILTER --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div>
                <label class="text-sm font-semibold mb-2 block">Search</label>
                <input
                    type="text"
                    wire:model.live.debounce.500ms="search"
                    placeholder="Cari device, actuator, ID, lokasi, value, atau timestamp..."
                    class="input input-bordered w-full"
                >
            </div>

            <div>
                <label class="text-sm font-semibold mb-2 block">List Device</label>
                <select wire:model.live="selectedDevice" class="select select-bordered w-full">
                    <option value="">Semua Device</option>

                    @foreach ($deviceList as $deviceOption)
                        <option value="{{ $deviceOption->id_esp }}">
                            {{ $deviceOption->name_esp }} - {{ $deviceOption->id_esp }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-semibold mb-2 block">List Actuator</label>
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
                        <th>Actuator</th>
                        <th>ID Act</th>
                        <th>Lokasi</th>
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
                                {{ $act->name_act }}
                            </td>

                            <td>
                                <span class="badge badge-outline">
                                    {{ $act->id_act }}
                                </span>
                            </td>

                            <td>
                                {{ $act->loc_esp ?? '-' }}
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