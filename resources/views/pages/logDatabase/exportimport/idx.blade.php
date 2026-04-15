<?php

use App\Imports\FullDeviceCsvImport;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public string $selectedDevice = '';
    public string $exportType = 'full';
    public $file;

    public function getDevices()
    {
        return DB::table('device_esp')
            ->orderBy('name_esp')
            ->get();
    }

    protected function exportParams(): array
    {
        return array_filter([
            'device' => $this->selectedDevice,
        ], fn ($value) => $value !== '');
    }

    public function exportCsv()
    {
        $route = match ($this->exportType) {
            'esp'    => 'export-import.device-esp.csv',
            'sensor' => 'export-import.device-sensor.csv',
            'act'    => 'export-import.device-act.csv',
            'status' => 'export-import.status-news.csv',
            default  => 'export-import.full.csv',
        };

        return $this->redirectRoute($route, $this->exportParams());
    }

    public function exportPdf()
    {
        $route = match ($this->exportType) {
            'esp'    => 'export-import.device-esp.pdf',
            'sensor' => 'export-import.device-sensor.pdf',
            'act'    => 'export-import.device-act.pdf',
            'status' => 'export-import.status-news.pdf',
            default  => 'export-import.full.pdf',
        };

        return $this->redirectRoute($route, $this->exportParams());
    }

    public function importCsv()
    {
        $this->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        try {
            app(FullDeviceCsvImport::class)->import($this->file->getRealPath());

            $this->reset('file');
            session()->forget('error');
            session()->flash('success', 'Import CSV berhasil. Device disimpan ke tabel device_esp dan status/news ke tabel status_news.');
        } catch (\Throwable $e) {
            $this->reset('file');
            session()->forget('success');
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        return $this->view([
            'devices' => $this->getDevices(),
        ]);
    }
};
?>

<div>
    <x-card title="Export & Import Device Data" shadow separator>

        {{-- DEVICE FILTER --}}
        <div class="mb-4">
            <label class="text-sm font-semibold block mb-2">
                Filter Device
            </label>

            <select wire:model="selectedDevice" class="select select-bordered w-full">
                <option value="">Semua Device</option>

                @foreach ($devices as $dev)
                    <option value="{{ $dev->id }}">
                        {{ $dev->name_esp }} ({{ $dev->id_esp }})
                    </option>
                @endforeach
            </select>
        </div>

        {{-- EXPORT TYPE --}}
        <div class="mb-5">
            <label class="text-sm font-semibold block mb-2">
                Data Type
            </label>

            <select wire:model="exportType" class="select select-bordered w-full">
                <option value="esp">Device ESP</option>
                <option value="sensor">Device Sensor</option>
                <option value="act">Device Actuator</option>
                <option value="status">Status News</option>
                <option value="full">Full Export</option>
            </select>
        </div>

        {{-- ACTION BUTTONS --}}
        <div class="flex gap-3 mb-6">
            <button type="button" wire:click="exportCsv" class="btn btn-primary flex-1">
                Export CSV
            </button>

            <button type="button" wire:click="exportPdf" class="btn btn-neutral flex-1">
                Export PDF
            </button>
        </div>

        {{-- IMPORT --}}
        <div class="border-t pt-5">
            <h3 class="font-semibold mb-3">
                Import FULL CSV
            </h3>

            <p class="text-sm opacity-70 mb-3">
                Import akan menyimpan data device ke tabel <code>device_esp</code>, data sensor ke <code>device_sensor</code>, data actuator ke <code>device_act</code>, dan status/news ke <code>status_news</code>.
            </p>

            <input
                type="file"
                wire:model="file"
                class="file-input file-input-bordered w-full"
            >

            @error('file')
                <div class="text-red-500 text-sm mt-1">
                    {{ $message }}
                </div>
            @enderror

            <button
                type="button"
                wire:click="importCsv"
                class="btn btn-warning w-full mt-3"
            >
                Import CSV
            </button>

            @if (session()->has('success'))
                <div class="alert alert-success mt-3">
                    {{ session('success') }}
                </div>
            @endif

            @if (session()->has('error'))
                <div class="alert alert-error mt-3">
                    {{ session('error') }}
                </div>
            @endif
        </div>

    </x-card>
</div>