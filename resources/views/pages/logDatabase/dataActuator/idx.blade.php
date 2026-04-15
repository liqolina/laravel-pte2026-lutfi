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

    public function getActs()
    {
        return DB::table('device_act as a')
            ->join('device_esp as d', 'd.id', '=', 'a.id_device')
            ->when($this->selectedDevice, function ($q) {
                $q->where('a.id_device', $this->selectedDevice);
            })
            ->orderByDesc('a.timestamp')
            ->select('a.*', 'd.name_esp')
            ->get();
    }

    public function render()
    {
        return $this->view([
            'devices' => $this->getDevices(),
            'acts'    => $this->getActs(),
        ]);
    }
};
?>

<div wire:poll.5s>
    <x-card title="Database | Device Actuator" shadow separator>

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