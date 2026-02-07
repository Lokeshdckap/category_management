<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;


class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        $admin->assignRole('admin');
    }
}
