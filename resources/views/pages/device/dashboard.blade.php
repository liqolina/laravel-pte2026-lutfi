<?php

use Livewire\Component;
use App\Models\Device\DeviceEsp;

new class extends Component
{
    public function render()
    {
        $devices = DeviceEsp::query()
            ->with([
                'sensors' => function ($query) {
                    $query->orderBy('name_sensor');
                },
                'acts' => function ($query) {
                    $query->orderBy('name_act');
                },
            ])
            ->orderBy('name_esp')
            ->get();

        $devices->transform(function ($device) {
            $device->sensors = $device->sensors
                ->groupBy('id_sensor')
                ->map(function ($rows) {
                    return $rows->sortByDesc('timestamp')->first();
                })
                ->sortBy('name_sensor')
                ->values();

            $device->acts = $device->acts
                ->groupBy('id_act')
                ->map(function ($rows) {
                    return $rows->sortByDesc('timestamp')->first();
                })
                ->sortBy('name_act')
                ->values();

            return $device;
        });

        return $this->view(['devices' => $devices]);
    }
};
?>

<div class="min-h-screen bg-slate-50 py-6">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Device Monitoring</h1>
            <p class="text-sm text-slate-500">ESP, sensor, dan actuator overview</p>
        </div>

        @if ($devices->isEmpty())
            <div class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center shadow-sm">
                <p class="text-sm text-slate-500">Belum ada data device.</p>
            </div>
        @else
            <div class="space-y-6">
                @foreach ($devices as $device)
                    <x-card class="rounded-xl border border-slate-200 shadow-sm" shadow>
                        <div class="space-y-5">
                            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3 text-sm text-slate-700">
                                <div><span class="font-semibold text-slate-900">Name:</span> {{ $device->name_esp }}</div>
                                <div><span class="font-semibold text-slate-900">ID:</span> {{ $device->id_esp }}</div>
                                <div><span class="font-semibold text-slate-900">MAC:</span> {{ $device->mac_esp }}</div>
                                <div><span class="font-semibold text-slate-900">IP:</span> {{ $device->ip_esp }}</div>
                                <div><span class="font-semibold text-slate-900">Location:</span> {{ $device->loc_esp }}</div>
                                <div><span class="font-semibold text-slate-900">Log Time:</span> {{ $device->log_time }}</div>
                            </div>

                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Sensors</h2>
                                </div>

                                @if ($device->sensors->isEmpty())
                                    <div class="rounded-lg border border-dashed border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">Belum ada sensor.</div>
                                @else
                                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
                                        @foreach ($device->sensors as $sensor)
                                            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                                <div class="mb-3">
                                                    <h3 class="text-sm font-semibold text-slate-900">{{ $sensor->name_sensor }}</h3>
                                                    <p class="text-xs text-slate-500">{{ $sensor->id_sensor }}</p>
                                                </div>
                                                <div class="space-y-1 text-xs text-slate-700">
                                                    <div>val_A: {{ $sensor->val_A }}</div>
                                                    <div>val_B: {{ $sensor->val_B }}</div>
                                                    <div>val_C: {{ $sensor->val_C }}</div>
                                                    <div>val_D: {{ $sensor->val_D }}</div>
                                                    <div>val_E: {{ $sensor->val_E }}</div>
                                                    <div>val_F: {{ $sensor->val_F }}</div>
                                                    <div>val_G: {{ $sensor->val_G }}</div>
                                                    <div>val_h: {{ $sensor->val_h }}</div>
                                                    <div class="pt-2 text-[11px] text-slate-500">{{ $sensor->timestamp }}</div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Actuators</h2>
                                </div>

                                @if ($device->acts->isEmpty())
                                    <div class="rounded-lg border border-dashed border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">Belum ada actuator.</div>
                                @else
                                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
                                        @foreach ($device->acts as $act)
                                            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                                <div class="mb-3">
                                                    <h3 class="text-sm font-semibold text-slate-900">{{ $act->name_act }}</h3>
                                                    <p class="text-xs text-slate-500">{{ $act->id_act }}</p>
                                                </div>
                                                <div class="space-y-1 text-xs text-slate-700">
                                                    <div>val_A: {{ $act->val_A }}</div>
                                                    <div>val_B: {{ $act->val_B }}</div>
                                                    <div>val_C: {{ $act->val_C }}</div>
                                                    <div>val_D: {{ $act->val_D }}</div>
                                                    <div class="pt-2 text-[11px] text-slate-500">{{ $act->timestamp }}</div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </x-card>
                @endforeach
            </div>
        @endif
    </div>
</div>
