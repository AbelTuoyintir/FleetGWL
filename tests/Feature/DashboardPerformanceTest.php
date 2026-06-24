<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Driver;
use App\Models\Region;
use App\Models\Maintenance;
use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;

class DashboardPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (empty(config('app.key'))) {
            $this->artisan('key:generate');
        }
    }

    public function test_dashboard_query_count()
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@gwc.com',
            'password' => Hash::make('password'),
            'role' => 'admin'
        ]);

        $region = Region::create(['name' => 'Accra West', 'code' => 'AW', 'status' => 'active']);

        // Create 10 vehicles
        for ($i = 0; $i < 10; $i++) {
            $vehicle = Vehicle::create([
                'registration_number' => 'REG' . $i,
                'make' => 'Toyota',
                'model' => 'Camry',
                'year' => 2020,
                'vehicle_type' => 'Saloon',
                'chassis_number' => 'CH' . $i,
                'status' => 'active',
                'region_id' => $region->id,
            ]);

            $driverUser = User::create([
                'name' => 'Driver ' . $i,
                'email' => 'driver' . $i . '@example.com',
                'password' => Hash::make('password'),
                'role' => 'driver'
            ]);

            $driver = Driver::create([
                'user_id' => $driverUser->id,
                'license_number' => 'LIC' . $i,
                'status' => 'active'
            ]);

            $vehicle->update(['assigned_driver_id' => $driver->id]);

            // Create some maintenance records
            Maintenance::create([
                'vehicle_id' => $vehicle->id,
                'maintenance_type' => 'Service',
                'maintenance_date' => now()->addDays(5),
                'status' => 'scheduled',
                'cost' => 100,
            ]);

            // Create some documents
            Document::create([
                'title' => 'Doc ' . $i,
                'slug' => 'doc-' . $i,
                'file_path' => 'path/to/file',
                'file_name' => 'file.pdf',
                'file_type' => 'application/pdf',
                'extension' => 'pdf',
                'document_type' => 'insurance',
                'vehicle_id' => $vehicle->id,
                'expiry_date' => now()->addDays(10),
                'status' => 'active',
            ]);
        }

        // Clear cache to ensure we measure query count
        Cache::forget('dashboard_complete_data');

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        // Output query count for baseline
        fwrite(STDOUT, "\nDashboard query count: " . $queryCount . "\n");

        $response->assertStatus(200);

        // Verify data correctness
        $response->assertViewHas('totalVehicles', 10);
        $response->assertViewHas('unassignedVehicles', 0); // All 10 were assigned in the loop
        $response->assertViewHas('criticalAlerts', function($alerts) {
            return $alerts->expired_insurances === 0 && // they expire in 10 days, not < now
                   $alerts->vehicles_needing_attention === 0;
        });
        $response->assertViewHas('vehicleUtilization', function($util) {
            return $util->total === 10 && $util->assigned === 10 && $util->utilization_rate == 100.0;
        });
        $response->assertViewHas('monthlySummary', function($summary) {
            return $summary->vehicles_added === 10;
        });
    }
}
