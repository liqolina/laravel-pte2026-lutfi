<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    use WithFileUploads;

    public string $selectedDevice = '';
    public string $exportType = 'sensor';
    public $file;

    /* =========================
        DEVICE LIST
    ========================== */
    public function getDevices()
    {
        return DB::table('device_esp')
            ->orderBy('name_esp')
            ->get();
    }

    /* =========================
        EXPORT CSV
    ========================== */
    public function exportCsv()
    {
        return match ($this->exportType) {
            'esp'    => redirect()->route('export-import.device-esp.csv'),
            'sensor' => redirect()->route('export-import.device-sensor.csv'),
            'act'    => redirect()->route('export-import.device-act.csv'),
            default  => redirect()->route('export-import.full.csv'),
        };
    }

    /* =========================
        EXPORT PDF
    ========================== */
    public function exportPdf()
    {
        return match ($this->exportType) {
            'esp'    => redirect()->route('export-import.device-esp.pdf'),
            'sensor' => redirect()->route('export-import.device-sensor.pdf'),
            'act'    => redirect()->route('export-import.device-act.pdf'),
            default  => redirect()->route('export-import.full.pdf'),
        };
    }

    /* =========================
        IMPORT FULL CSV
    ========================== */
    public function importCsv()
    {
        $this->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        $path = $this->file->getRealPath();
        $handle = fopen($path, 'r');

        DB::transaction(function () use ($handle) {

            $table = null;
            $header = [];
            $isHeader = false;

            while (($row = fgetcsv($handle)) !== false) {

                // 🔥 FIX: skip empty row
                if (!$row || count(array_filter($row)) == 0) {
                    continue;
                }

                // 🔥 FIX: remove BOM karakter
                $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', $row[0]);

                // TABLE DETECT
                if ($row[0] === '#TABLE') {
                    $table = $row[1] ?? null;
                    $isHeader = true;
                    continue;
                }

                // HEADER
                if ($isHeader) {
                    $header = $row;
                    $isHeader = false;
                    continue;
                }

                // DATA
                if ($table && $header) {

                    $data = array_combine($header, $row);

                    if (!$data || !isset($data['id'])) {
                        continue;
                    }

                    foreach ($data as $k => $v) {
                        $data[$k] = ($v === '' ? null : $v);
                    }

                    DB::table($table)->updateOrInsert(
                        ['id' => $data['id']],
                        $data
                    );
                }
            }

            fclose($handle);
        });

        session()->flash('success', 'Import berhasil (FIXED VERSION)');
    }

    public function render()
    {
        return $this->view([
            'devices' => $this->getDevices(),
        ]);
    }
};
?>

<div class="max-w-2xl mx-auto p-6">

    <x-card title="Export & Import Device Data" shadow separator>

        {{-- DEVICE FILTER --}}
        <div class="mb-4">
            <label class="text-sm font-semibold block mb-2">
                Filter Device
            </label>

            <select wire:model="selectedDevice"
                class="select select-bordered w-full">

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

            <select wire:model="exportType"
                class="select select-bordered w-full">

                <option value="esp">Device ESP</option>
                <option value="sensor">Device Sensor</option>
                <option value="act">Device Actuator</option>
                <option value="full">Full Export</option>

            </select>
        </div>

        {{-- ACTION BUTTONS --}}
        <div class="flex gap-3 mb-6">

            <button class="btn btn-primary flex-1">
                Export CSV
            </button>

            <button class="btn btn-neutral flex-1">
                Export PDF
            </button>

        </div>

        {{-- IMPORT --}}
        <div class="border-t pt-5">

            <h3 class="font-semibold mb-3">
                Import FULL CSV
            </h3>

            <input type="file"
                wire:model="file"
                class="file-input file-input-bordered w-full">

            @error('file')
                <div class="text-red-500 text-sm mt-1">
                    {{ $message }}
                </div>
            @enderror

            <button wire:click="importCsv"
                class="btn btn-warning w-full mt-3">

                Import CSV
            </button>

            @if (session()->has('success'))
                <div class="alert alert-success mt-3">
                    {{ session('success') }}
                </div>
            @endif

        </div>

    </x-card>

</div>