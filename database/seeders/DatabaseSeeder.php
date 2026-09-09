<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Registrasi publik dimatikan (§7.1), jadi akun owner dibuat di sini.
     */
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'owner@nadifotocopy.test'],
            [
                'name' => 'Owner Nadi\'s Fotocopy',
                'password' => 'password',
                'role' => UserRole::Owner,
                'email_verified_at' => now(),
            ],
        );

        $this->call(StoreSeeder::class);
    }
}
