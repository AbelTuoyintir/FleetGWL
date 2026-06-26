<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Driver;
use App\Models\Region;
use App\Models\Document;
use App\Models\Maintenance;
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

        Cache::flush();
    }

    public function test_dashboard_query_count()
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin'
        ]);

        $region = Region::create(['name' => 'Test Region', 'code' => 'TR']);

        // Create some vehicles
        for ($i = 0; $i < 5; $i++) {
            Vehicle::create([
                'registration_number' => 'REG' . $i,
                'make' => 'Toyota',
                'model' => 'Camry',
                'year' => 2020,
                'vehicle_type' => 'Saloon',
                'chassis_number' => 'CH' . $i,
                'status' => $i % 2 == 0 ? 'active' : 'needs_repair',
                'region_id' => $region->id
            ]);
        }

        // Create some documents
        for ($i = 0; $i < 5; $i++) {
            Document::create([
                'title' => 'Test Document ' . $i,
                'vehicle_id' => 1,
                'document_type' => $i % 2 == 0 ? 'insurance' : 'registration',
                'reference_number' => 'DOC' . $i,
                'expiry_date' => now()->subDays(1),
                'status' => 'active',
                'file_path' => 'documents/test' . $i . '.pdf',
                'file_name' => 'test' . $i . '.pdf',
                'file_type' => 'application/pdf',
                'extension' => 'pdf'
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        // We bypass cache by calling the route which will call gatherDashboardData because cache is flushed
        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        // Filter out session/auth related queries if possible, but let's just see total first
        fwrite(STDOUT, "\nDashboard total query count: " . $queryCount . "\n");

        $response->assertStatus(200);
    }
}
