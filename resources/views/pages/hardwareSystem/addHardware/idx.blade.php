<?php

use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component
{
    public string $id_esp = '';
    public string $name_esp = '';
    public string $topic_publish = '';
    public string $topic_subscribe = '';

    public function getHardware()
    {
        return DB::table('hardware_esp')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();
    }

    public function save()
    {
        $validated = $this->validate([
            'id_esp' => 'required|string|max:255|unique:hardware_esp,id_esp',
            'name_esp' => 'nullable|string|max:255',
            'topic_publish' => 'nullable|string|max:255',
            'topic_subscribe' => 'nullable|string|max:255',
        ], [
            'id_esp.required' => 'ID ESP wajib diisi.',
            'id_esp.unique' => 'ID ESP sudah terdaftar.',
        ]);

        DB::table('hardware_esp')->insert([
            'id_esp' => trim($validated['id_esp']),
            'name_esp' => trim((string) ($validated['name_esp'] ?? '')) ?: null,
            'topic_publish' => trim((string) ($validated['topic_publish'] ?? '')) ?: null,
            'topic_subscribe' => trim((string) ($validated['topic_subscribe'] ?? '')) ?: null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->reset([
            'id_esp',
            'name_esp',
            'topic_publish',
            'topic_subscribe',
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

<div>
    <x-card title="Tambah Hardware ESP" shadow separator>

        @if (session()->has('success'))
            <div class="alert alert-success mb-5">
                {{ session('success') }}
            </div>
        @endif

        <form wire:submit="save" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">

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

            <div>
                <label class="text-sm font-semibold block mb-2">
                    Topic Subscribe
                </label>

                <input
                    type="text"
                    wire:model="topic_subscribe"
                    class="input input-bordered w-full"
                    placeholder="Contoh: device/esp001/subscribe"
                >

                @error('topic_subscribe')
                    <div class="text-red-500 text-sm mt-1">
                        {{ $message }}
                    </div>
                @enderror
            </div>

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

        <div class="overflow-x-auto rounded-2xl border border-base-300 bg-base-100">
            <table class="table table-zebra table-sm">
                <thead class="bg-base-200">
                    <tr>
                        <th>No</th>
                        <th>ID ESP</th>
                        <th>Name ESP</th>
                        <th>Topic Publish</th>
                        <th>Topic Subscribe</th>
                        <th>Updated At</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($hardwareList as $index => $hardware)
                        <tr wire:key="hardware-{{ $hardware->id }}">
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $hardware->id_esp }}</td>
                            <td>{{ $hardware->name_esp ?? '-' }}</td>
                            <td>{{ $hardware->topic_publish ?? '-' }}</td>
                            <td>{{ $hardware->topic_subscribe ?? '-' }}</td>
                            <td>{{ $hardware->updated_at ?? '-' }}</td>
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