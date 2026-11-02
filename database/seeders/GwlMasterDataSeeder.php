<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GwlMasterDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            /*
            |--------------------------------------------------------------------------
            | GREATER ACCRA OPERATIONAL REGIONS
            |--------------------------------------------------------------------------
            */

            $regions = [
                ['name' => 'Accra North', 'code' => 'ACC-NORTH'],
                ['name' => 'Accra West', 'code' => 'ACC-WEST'],
                ['name' => 'Accra East', 'code' => 'ACC-EAST'],
                ['name' => 'Accra South', 'code' => 'ACC-SOUTH'],
                ['name' => 'Tema East', 'code' => 'TEMA-EAST'],
                ['name' => 'Tema West', 'code' => 'TEMA-WEST'],
            ];

            foreach ($regions as $region) {
                DB::table('regions')->updateOrInsert(
                    ['code' => $region['code']],
                    [
                        'name' => $region['name'],
                        'status' => 'active',
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            $regionIds = DB::table('regions')
                ->whereIn('code', array_column($regions, 'code'))
                ->pluck('id', 'code');

            /*
            |--------------------------------------------------------------------------
            | DISTRICTS
            |--------------------------------------------------------------------------
            */

            $districts = [
                // Accra North
                ['name' => 'Accra North East', 'code' => 'ACC-NE', 'region' => 'ACC-NORTH'],
                ['name' => 'Agbogba', 'code' => 'AGB', 'region' => 'ACC-NORTH'],
                ['name' => 'Adenta', 'code' => 'ADT', 'region' => 'ACC-NORTH'],
                ['name' => 'Dodowa', 'code' => 'DOD', 'region' => 'ACC-NORTH'],

                // Accra West
                ['name' => 'Darkuman-Gbawe', 'code' => 'DARK-GBAWE', 'region' => 'ACC-WEST'],
                ['name' => 'Kaneshie', 'code' => 'KAN', 'region' => 'ACC-WEST'],
                ['name' => 'Odorkor', 'code' => 'ODK', 'region' => 'ACC-WEST'],
                ['name' => 'Sowutuom', 'code' => 'SOW', 'region' => 'ACC-WEST'],
                ['name' => 'Amasaman', 'code' => 'AMA', 'region' => 'ACC-WEST'],
            ];

            foreach ($districts as $district) {
                DB::table('districts')->updateOrInsert(
                    ['code' => $district['code']],
                    [
                        'name' => $district['name'],
                        'region_id' => $regionIds[$district['region']],
                        'status' => 'active',
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | STATIONS
            |--------------------------------------------------------------------------
            */

            $stations = [
                [
                    'name' => 'Weija Headworks',
                    'code' => 'ACC-WEIJA',
                    'type' => 'headworks',
                    'region' => 'ACC-WEST',
                ],
                [
                    'name' => 'Kpong Headworks',
                    'code' => 'ACC-KPONG',
                    'type' => 'headworks',
                    'region' => 'ACC-EAST',
                ],
            ];

            foreach ($stations as $station) {
                DB::table('stations')->updateOrInsert(
                    ['code' => $station['code']],
                    [
                        'name' => $station['name'],
                        'type' => $station['type'],
                        'region_id' => $regionIds[$station['region']],
                        'district_id' => null,
                        'status' => 'active',
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

        });

        $this->command->info(
            'Greater Accra GWL regions, districts and stations seeded successfully!'
        );
    }
}
