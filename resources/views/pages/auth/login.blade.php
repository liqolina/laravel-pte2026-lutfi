<?php

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;
use Livewire\Attributes\Layout;

new #[Layout('layouts::guest')] class extends Component
{
    use Toast;

    public $email;
    public $password;

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required|min:6',
    ];

    public function login()
    {
        $this->validate();

        if (Auth::attempt([
            'email' => $this->email,
            'password' => $this->password
        ])) {
<<<<<<< HEAD
            return redirect()->route('admin');
=======
            return redirect()->route('dashboard');
>>>>>>> 4a671bb (Pembaruan kode)
        }

        $this->error('Email atau password salah', position: 'toast-top toast-center');
    }
};
?>

<div class="min-h-screen flex items-center justify-center bg-base-200 px-4 py-8 sm:px-6 lg:px-8">

    <div class="w-full max-w-md bg-base-100 rounded-2xl shadow-xl border border-base-300 p-6 sm:p-8">

        <div class="mb-6 text-center">
            <h1 class="text-2xl font-bold text-base-content">
                Login
            </h1>
            <p class="mt-2 text-sm text-base-content/60">
                Masuk menggunakan akun Anda
            </p>
        </div>

        <form wire:submit.prevent="login" class="space-y-5">

            <x-input
                label="Email"
                wire:model="email"
                placeholder="Masukkan email"
                icon="o-envelope"
                hint="Gunakan email yang terdaftar"
            />

            <x-password
                label="Password"
                wire:model="password"
                placeholder="Masukkan password"
                icon="o-key"
                hint="Minimal 6 karakter"
                right
            />

            <div class="pt-2">
                <x-button
                    type="submit"
                    class="btn-primary w-full"
                    label="Login"
                    spinner="login"
                />
            </div>

        </form>

    </div>

</div>