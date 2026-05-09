<?php

use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component
{
    public string $id_esp = '';
    public string $name_esp = '';
    public string $topic_publish = '';
    public string $topic_subcribe = '';

    public function getHardware()
    {
        return DB::table('hardware_esp')
            ->orderByDesc('id')
            ->get();
    }

    public function save()
    {
        $this->validate([
            'id_esp' => 'nullable|required_without:name_esp|string|max:255',
            'name_esp' => 'nullable|required_without:id_esp|string|max:255',
            'topic_publish' => 'required|string|max:255',
            'topic_subcribe' => 'required|string|max:255',
        ], [
            'id_esp.required_without' => 'ID ESP atau Name ESP wajib diisi salah satu.',
            'name_esp.required_without' => 'Name ESP atau ID ESP wajib diisi salah satu.',
            'topic_publish.required' => 'Topic publish wajib diisi.',
            'topic_subcribe.required' => 'Topic subcribe wajib diisi.',
        ]);

        /*
         * Karena migration hardware_esp membuat id_esp dan name_esp sebagai string wajib,
         * maka jika salah satu kosong, nilainya otomatis diisi dari field yang satunya.
         * Jadi database tidak perlu diubah.
         */
        $idEsp = trim($this->id_esp) !== ''
            ? trim($this->id_esp)
            : trim($this->name_esp);

        $nameEsp = trim($this->name_esp) !== ''
            ? trim($this->name_esp)
            : trim($this->id_esp);

        DB::table('hardware_esp')->insert([
            'id_esp' => $idEsp,
            'name_esp' => $nameEsp,
            'topic_publish' => trim($this->topic_publish),
            'topic_subcribe' => trim($this->topic_subcribe),
            'timestamp' => now(),
        ]);

        $this->reset([
            'id_esp',
            'name_esp',
            'topic_publish',
            'topic_subcribe',
        ]);

        session()->flash('success', 'Hardware ESP berhasil ditambahkan.');
    }

    public function deleteHardware(int $id)
    {
        DB::table('hardware_esp')
            ->where('id', $id)
            ->delete();

        session()->flash('success', 'Hardware ESP berhasil dihapus.');
    }

    public function render()
    {
        return $this->view([
            'hardwareList' => $this->getHardware(),
        ]);
    }
};
?>

<div class="max-w-6xl mx-auto p-6">
    <x-card title="Tambah Hardware ESP" shadow separator>

        {{-- ALERT SUCCESS --}}
        @if (session()->has('success'))
            <div class="alert alert-success mb-5">
                {{ session('success') }}
            </div>
        @endif

        {{-- FORM TAMBAH HARDWARE --}}
        <form wire:submit="save" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">

            {{-- ID ESP --}}
            <div>
                <label class="text-sm font-semibold block mb-2">
                    ID ESP
                </label>

                <input
                    type="text"
                    wire:model="id_esp"
                    class="input input-bordered w-full"
                    placeholder="Contoh: ESP001"
                >

                @error('id_esp')
                    <div class="text-red-500 text-sm mt-1">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            {{-- NAME ESP --}}
            <div>
                <label class="text-sm font-semibold block mb-2">
                    Name ESP
                </label>

                <input
                    type="text"
                    wire:model="name_esp"
                    class="input input-bordered w-full"
                    placeholder="Contoh: ESP Ruang Server"
                >

                @error('name_esp')
                    <div class="text-red-500 text-sm mt-1">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            {{-- TOPIC PUBLISH --}}
            <div>
                <label class="text-sm font-semibold block mb-2">
                    Topic Publish
                </label>

                <input
                    type="text"
                    wire:model="topic_publish"
                    class="input input-bordered w-full"
                    placeholder="Contoh: device/esp001/publish"
                >

                @error('topic_publish')
                    <div class="text-red-500 text-sm mt-1">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            {{-- TOPIC SUBCRIBE --}}
            <div>
                <label class="text-sm font-semibold block mb-2">
                    Topic Subcribe
                </label>

                <input
                    type="text"
                    wire:model="topic_subcribe"
                    class="input input-bordered w-full"
                    placeholder="Contoh: device/esp001/subcribe"
                >

                @error('topic_subcribe')
                    <div class="text-red-500 text-sm mt-1">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            {{-- BUTTON --}}
            <div class="md:col-span-2 flex justify-end gap-3">
                <button
                    type="button"
                    wire:click="$set('id_esp', '')"
                    class="btn btn-neutral"
                >
                    Reset ID
                </button>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Simpan Hardware
                </button>
            </div>
        </form>

        {{-- TABLE DATA HARDWARE --}}
        <div class="overflow-x-auto border rounded-lg">
            <table class="table table-zebra w-full">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>ID ESP</th>
                        <th>Name ESP</th>
                        <th>Topic Publish</th>
                        <th>Topic Subcribe</th>
                        <th>Timestamp</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($hardwareList as $index => $hardware)
                        <tr wire:key="hardware-{{ $hardware->id }}">
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $hardware->id_esp }}</td>
                            <td>{{ $hardware->name_esp }}</td>
                            <td>{{ $hardware->topic_publish }}</td>
                            <td>{{ $hardware->topic_subcribe }}</td>
                            <td>
                                {{ $hardware->timestamp ?? '-' }}
                            </td>
                            <td class="text-center">
                                <button
                                    type="button"
                                    wire:click="deleteHardware({{ $hardware->id }})"
                                    wire:confirm="Yakin ingin menghapus hardware ini?"
                                    class="btn btn-error btn-sm"
                                >
                                    Hapus
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-6 text-gray-500">
                                Belum ada data hardware ESP.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </x-card>
</div>