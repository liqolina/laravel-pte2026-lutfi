<?php

use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

new class extends Component
{
    public ?int $clientId = null;
    public string $login = '';
    public string $password = '';
    public string $role = 'client';
    public string $expired_at = '';

    public function mount(): void
    {
        $this->expired_at = now()->toDateString();
    }

    public function getClients()
    {
        return DB::table('atj_clients as c')
            ->leftJoin('users as u', 'u.id', '=', 'c.user_id')
            ->select(
                'c.id',
                'c.user_id',
                'c.login_identifier',
                'c.role',
                'c.expired_at',
                'c.created_at',
                'u.name as username',
                'u.email'
            )
            ->orderByDesc('c.id')
            ->get();
    }

    protected function rules(): array
    {
        return [
            'login' => 'required|string|max:255',
            'password' => $this->clientId ? 'nullable|string|min:6|max:255' : 'required|string|min:6|max:255',
            'role' => 'required|in:admin,client',
            'expired_at' => 'required|date',
        ];
    }

    protected function messages(): array
    {
        return [
            'login.required' => 'Username atau email wajib diisi.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 6 karakter.',
            'role.required' => 'Role wajib dipilih.',
            'role.in' => 'Role harus admin atau client.',
            'expired_at.required' => 'Tanggal expired wajib diisi.',
        ];
    }

    protected function normalizeLogin(string $value): array
    {
        $value = trim($value);
        $isEmail = filter_var($value, FILTER_VALIDATE_EMAIL) !== false;

        if ($isEmail) {
            $username = strstr($value, '@', true) ?: $value;
            $email = $value;
        } else {
            $username = $value;
            $email = $value . '@local.test';
        }

        return [
            'login_identifier' => $value,
            'username' => $username,
            'email' => strtolower($email),
        ];
    }

    protected function validateUniqueCredentials(array $credentials): void
    {
        $userQuery = DB::table('users');
        $clientQuery = DB::table('atj_clients');

        if ($this->clientId) {
            $currentClient = DB::table('atj_clients')->where('id', $this->clientId)->first();

            if ($currentClient?->user_id) {
                $userQuery->where('id', '!=', $currentClient->user_id);
            }

            $clientQuery->where('id', '!=', $this->clientId);
        }

        $usernameExists = (clone $userQuery)->where('name', $credentials['username'])->exists();
        $emailExists = (clone $userQuery)->where('email', $credentials['email'])->exists();
        $loginExists = $clientQuery->where('login_identifier', $credentials['login_identifier'])->exists();

        if ($usernameExists) {
            $this->addError('login', 'Username sudah digunakan.');
        }

        if ($emailExists) {
            $this->addError('login', 'Email sudah digunakan.');
        }

        if ($loginExists) {
            $this->addError('login', 'Username atau email sudah terdaftar pada client.');
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            throw \Illuminate\Validation\ValidationException::withMessages($this->getErrorBag()->toArray());
        }
    }

    public function save(): void
    {
        $this->resetErrorBag();
        $this->validate();

        $credentials = $this->normalizeLogin($this->login);
        $this->validateUniqueCredentials($credentials);

        DB::beginTransaction();

        try {
            if ($this->clientId) {
                $client = DB::table('atj_clients')->where('id', $this->clientId)->first();

                if (!$client || !$client->user_id) {
                    throw new \RuntimeException('Data client tidak ditemukan.');
                }

                $userPayload = [
                    'name' => $credentials['username'],
                    'email' => $credentials['email'],
                    'updated_at' => now(),
                ];

                if (trim($this->password) !== '') {
                    $userPayload['password'] = Hash::make($this->password);
                }

                DB::table('users')
                    ->where('id', $client->user_id)
                    ->update($userPayload);

                DB::table('atj_clients')
                    ->where('id', $this->clientId)
                    ->update([
                        'login_identifier' => $credentials['login_identifier'],
                        'role' => $this->role,
                        'expired_at' => $this->expired_at,
                        'updated_at' => now(),
                    ]);

                session()->flash('success', 'Akun client berhasil diperbarui.');
            } else {
                $userId = DB::table('users')->insertGetId([
                    'name' => $credentials['username'],
                    'email' => $credentials['email'],
                    'password' => Hash::make($this->password),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('atj_clients')->insert([
                    'user_id' => $userId,
                    'login_identifier' => $credentials['login_identifier'],
                    'role' => $this->role,
                    'expired_at' => $this->expired_at,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                session()->flash('success', 'Akun client berhasil ditambahkan.');
            }

            DB::commit();
            $this->resetForm();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            logger()->error('Save client failed', ['message' => $e->getMessage()]);
            $this->addError('login', app()->environment('local') ? ('Gagal menyimpan data client: ' . $e->getMessage()) : 'Gagal menyimpan data client. Silakan cek konfigurasi tabel users dan atj_clients.');
        }
    }

    public function edit(int $id): void
    {
        $client = DB::table('atj_clients')->where('id', $id)->first();

        if (!$client) {
            session()->flash('success', 'Data client tidak ditemukan.');
            return;
        }

        $this->clientId = (int) $client->id;
        $this->login = (string) $client->login_identifier;
        $this->password = '';
        $this->role = (string) $client->role;
        $this->expired_at = $client->expired_at
            ? Carbon::parse($client->expired_at)->toDateString()
            : now()->toDateString();
    }

    public function deleteClient(int $id): void
    {
        DB::beginTransaction();

        try {
            $client = DB::table('atj_clients')->where('id', $id)->first();

            if ($client) {
                DB::table('atj_clients')->where('id', $id)->delete();

                if (!empty($client->user_id)) {
                    DB::table('users')->where('id', $client->user_id)->delete();
                }
            }

            DB::commit();
            session()->flash('success', 'Akun client berhasil dihapus.');

            if ($this->clientId === $id) {
                $this->resetForm();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            logger()->error('Delete client failed', ['message' => $e->getMessage()]);
            $this->addError('login', app()->environment('local') ? ('Gagal menghapus akun client: ' . $e->getMessage()) : 'Gagal menghapus akun client.');
        }
    }

    public function loginAs(int $id)
    {
        $client = DB::table('atj_clients')->where('id', $id)->first();

        if (!$client || empty($client->user_id)) {
            $this->addError('login', 'User client tidak ditemukan.');
            return;
        }

        if (!empty($client->expired_at) && Carbon::parse($client->expired_at)->endOfDay()->isPast()) {
            $this->addError('login', 'Akun ini sudah expired dan tidak bisa dipakai login.');
            return;
        }

        $user = DB::table('users')->where('id', $client->user_id)->first();

        if (!$user) {
            $this->addError('login', 'User client tidak ditemukan di tabel users.');
            return;
        }

        Auth::login($user);

        return redirect()->route($client->role === 'admin' ? 'dashboard' : 'client');
    }

    public function resetForm(): void
    {
        $this->reset(['clientId', 'login', 'password']);
        $this->role = 'client';
        $this->expired_at = now()->toDateString();
        $this->resetErrorBag();
    }

    public function render()
    {
        return $this->view([
            'clientList' => $this->getClients(),
        ]);
    }
};
?>

<div>
    <x-card title="Tambah Client / Login Account" shadow separator>

        @if (session()->has('success'))
            <div class="alert alert-success mb-5">
                {{ session('success') }}
            </div>
        @endif

        <form wire:submit="save" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="text-sm font-semibold block mb-2">
                    Username atau Email
                </label>

                <input
                    type="text"
                    wire:model="login"
                    class="input input-bordered w-full"
                    placeholder="Contoh: client01 atau client01@mail.com"
                >

                @error('login')
                    <div class="text-red-500 text-sm mt-1">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div>
                <label class="text-sm font-semibold block mb-2">
                    Password
                </label>

                <input
                    type="password"
                    wire:model="password"
                    class="input input-bordered w-full"
                    placeholder="Minimal 6 karakter"
                >

                @if($clientId)
                    <div class="text-xs text-gray-500 mt-1">
                        Kosongkan password jika tidak ingin diubah.
                    </div>
                @endif

                @error('password')
                    <div class="text-red-500 text-sm mt-1">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div>
                <label class="text-sm font-semibold block mb-2">
                    Admin atau Client
                </label>

                <select wire:model="role" class="select select-bordered w-full">
                    <option value="admin">Admin</option>
                    <option value="client">Client</option>
                </select>

                @error('role')
                    <div class="text-red-500 text-sm mt-1">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div>
                <label class="text-sm font-semibold block mb-2">
                    Expired
                </label>

                <input
                    type="date"
                    wire:model="expired_at"
                    class="input input-bordered w-full"
                >

                @error('expired_at')
                    <div class="text-red-500 text-sm mt-1">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="md:col-span-2 flex justify-end gap-3">
                <button
                    type="button"
                    wire:click="resetForm"
                    class="btn btn-neutral"
                >
                    Reset Form
                </button>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    {{ $clientId ? 'Update Client' : 'Simpan Client' }}
                </button>
            </div>
        </form>

        <div class="overflow-x-auto rounded-2xl border border-base-300 bg-base-100">
            <table class="table table-zebra table-sm">
                <thead class="bg-base-200">
                    <tr>
                        <th>No</th>
                        <th>Login</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Expired</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($clientList as $index => $client)
                        @php
                            $isExpired = !empty($client->expired_at) && \Carbon\Carbon::parse($client->expired_at)->endOfDay()->isPast();
                        @endphp
                        <tr wire:key="client-{{ $client->id }}">
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $client->login_identifier }}</td>
                            <td>{{ $client->username ?? '-' }}</td>
                            <td>{{ $client->email ?? '-' }}</td>
                            <td>{{ ucfirst($client->role) }}</td>
                            <td>{{ $client->expired_at ?? '-' }}</td>
                            <td>
                                <span class="badge {{ $isExpired ? 'badge-error' : 'badge-success' }}">
                                    {{ $isExpired ? 'Expired' : 'Active' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="flex flex-wrap justify-center gap-2">
                                    <button
                                        type="button"
                                        wire:click="edit({{ $client->id }})"
                                        class="btn btn-warning btn-sm"
                                    >
                                        Edit
                                    </button>

                                    <button
                                        type="button"
                                        wire:click="deleteClient({{ $client->id }})"
                                        wire:confirm="Yakin ingin menghapus akun ini?"
                                        class="btn btn-error btn-sm"
                                    >
                                        Hapus
                                    </button>

                                    <!-- <button
                                        type="button"
                                        wire:click="loginAs({{ $client->id }})"
                                        class="btn btn-info btn-sm"
                                    >
                                        Login As
                                    </button> -->
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-6 text-gray-500">
                                Belum ada data client.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>