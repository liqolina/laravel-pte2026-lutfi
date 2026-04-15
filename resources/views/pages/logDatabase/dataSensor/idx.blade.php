<?php

use Livewire\Component;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public string $selectedDevice = '';

    public function getDevices()
    {
        return DB::table('device_esp')
            ->orderBy('name_esp')
            ->get();
    }

    public function getSensors()
    {
        return DB::table('device_sensor as s')
            ->join('device_esp as d', 'd.id', '=', 's.id_device')
            ->when($this->selectedDevice, function ($q) {
                $q->where('s.id_device', $this->selectedDevice);
            })
            ->orderByDesc('s.timestamp')
            ->select('s.*', 'd.name_esp')
            ->get();
    }

    public function render()
    {
        return $this->view([
            'devices' => $this->getDevices(),
            'sensors' => $this->getSensors(),
        ]);
    }
};
?>

<div wire:poll.5s>
    <x-card title="Database | Device Sensor" shadow separator>

        {{-- FILTER DEVICE --}}
        <div class="mb-6">
            <label class="text-sm font-semibold mb-2 block">Device</label>

            <select wire:model.live="selectedDevice" class="select select-bordered w-full">
                <option value="">Semua Device</option>

                @foreach ($devices as $dev)
                    <option value="{{ $dev->id }}">
                        {{ $dev->name_esp }} - {{ $dev->id_esp }}
                    </option>
                @endforeach
            </select>
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
</div>