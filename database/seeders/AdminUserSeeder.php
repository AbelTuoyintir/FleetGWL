<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@fleetgwl.com'],
            [
                'name' => 'System Administrator',
                'email' => 'admin@fleetgwl.com',
                'phone' => '0240000000',
                'role' => 'admin',
                'photo' => null,
                'password' => Hash::make('password'),
            ]
        );
    }
}

