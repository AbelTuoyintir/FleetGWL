<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
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
        $company = Company::updateOrCreate(
            ['slug' => 'gwc'],
            [
                'name' => 'Ghana Water Company',
                'slug' => 'gwc',
                'status' => 'active',
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@gwc.com'],
            [
                'name' => 'System Administrator',
                'email' => 'admin@gwc.com',
                'phone' => '0240000001',
                'role' => 'admin',
                'company_id' => $company->id,
                'photo' => null,
                'password' => Hash::make('password'),
            ],
        );
    }
}

