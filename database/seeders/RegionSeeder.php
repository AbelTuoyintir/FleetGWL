<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Region;

class RegionSeeder extends Seeder
{
    public function run(): void
    {
        // Generate 10 fake regions
        // \App\Models\Region::truncate();
        \App\Models\Region::factory()->count(10)->create();
    }
}
