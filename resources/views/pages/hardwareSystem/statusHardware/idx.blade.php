<?php

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

new class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $selectedDevice = '';
    public int $perPage = 10;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedDevice(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function getDevices()
    {
        return DB::table('hardware_esp')
            ->select('id_esp', 'name_esp')
            ->distinct()
            ->orderBy('name_esp')
            ->orderBy('id_esp')
            ->get();
    }

    public function getHardwareRows()
    {
        $hasDeviceEsp = Schema::hasTable('device_esp');

        $query = DB::table('hardware_esp as h');

        if ($hasDeviceEsp) {
            $query->leftJoin('device_esp as d', 'h.id_esp', '=', 'd.id_esp');
        }

        $updatedAtExpression = $hasDeviceEsp
            ? "COALESCE(d.timestamp, d.updated_at, d.created_at, h.updated_at, h.created_at)"
            : "COALESCE(h.updated_at, h.created_at)";

        $deviceTimestampExpression = $hasDeviceEsp
            ? 'd.timestamp'
            : 'NULL';

        return $query
            ->select([
                'h.id',
                'h.id_esp',
                'h.name_esp',
                'h.topic_publish',
                'h.topic_subscribe',
                DB::raw("{$updatedAtExpression} as updated_at_value"),
                DB::raw("{$deviceTimestampExpression} as device_timestamp"),
            ])
            ->when($this->selectedDevice !== '', function ($query) {
                $query->where('h.id_esp', $this->selectedDevice);
            })
            ->when($this->search !== '', function ($query) {
                $search = trim($this->search);

                $query->where(function ($q) use ($search) {
                    $q->where('h.id_esp', 'like', '%' . $search . '%')
                        ->orWhere('h.name_esp', 'like', '%' . $search . '%')
                        ->orWhere('h.topic_publish', 'like', '%' . $search . '%')
                        ->orWhere('h.topic_subscribe', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('h.name_esp')
            ->orderBy('h.id_esp')
            ->paginate($this->perPage);
    }

    protected function resolveConnectionStatus(?string $timestamp): string
    {
        if (blank($timestamp)) {
            return 'OFFLINE';
        }

        try {
            $now = now();
            $lastSeen = Carbon::parse($timestamp);

            return $lastSeen->betweenIncluded(
                $now->copy()->subSeconds(30),
                $now
            ) ? 'ONLINE' : 'OFFLINE';
        } catch (\Throwable $e) {
            return 'OFFLINE';
        }
    }

    public function render()
    {
        $devices = $this->getHardwareRows();

        $devices->getCollection()->transform(function ($device) {
            $device->status_connection = $this->resolveConnectionStatus($device->device_timestamp);

            return $device;
        });

        return $this->view([
            'deviceList' => $this->getDevices(),
            'devices' => $devices,
        ]);
    }
};
?>

<div wire:poll.5s>
    <x-card title="Database | Hardware ESP" shadow separator>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">

            <div>
                <label class="text-sm font-semibold mb-2 block">
                    Search
                </label>
                <input
                    type="text"
                    wire:model.live.debounce.500ms="search"
                    placeholder="Cari ID device, nama, topic publish, atau topic subscribe..."
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
                            {{ $deviceOption->name_esp ?: 'Tanpa Nama' }} ({{ $deviceOption->id_esp }})
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

        <div class="overflow-x-auto rounded-2xl border border-base-300 bg-base-100">
            <table class="table table-zebra table-sm">
                <thead class="bg-base-200">
                    <tr>
                        <th>No</th>
                        <th>ID Device</th>
                        <th>Topic Publish</th>
                        <th>Topic Subscribe</th>
                        <th>Status</th>
                        <th>Updated At</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($devices as $index => $device)
                        <tr>
                            <td class="font-semibold">
                                {{ ($devices->firstItem() ?? 1) + $index }}
                            </td>

                            <td>
                                <span class="badge badge-outline">
                                    {{ $device->id_esp }}
                                </span>
                            </td>

                            <td class="whitespace-nowrap">
                                {{ $device->topic_publish ?: '-' }}
                            </td>

                            <td class="whitespace-nowrap">
                                {{ $device->topic_subscribe ?: '-' }}
                            </td>

                            <td>
                                @if ($device->status_connection === 'ONLINE')
                                    <span class="badge badge-success">
                                        ONLINE
                                    </span>
                                @else
                                    <span class="badge badge-error">
                                        OFFLINE
                                    </span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap">
                                {{ $device->updated_at_value ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-8 opacity-60">
                                Data hardware ESP belum tersedia
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