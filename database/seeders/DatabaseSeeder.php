<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('program', 'web');

        $user = User::firstOrCreate(
            ['email' => 'admin@bems.id'],
            [
                'name' => 'Admin',
                'password' => Hash::make('Ddw9889##'),
            ]
        );

        $user->assignRole('admin');
    }
}
