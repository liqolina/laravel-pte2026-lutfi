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
        // User::factory(10)->create();

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'program']);

        $user = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@bems.id',
            'password' => Hash::make('Ddw9889##'),
        ]);

        $user->assignRole('admin');
    }
}
