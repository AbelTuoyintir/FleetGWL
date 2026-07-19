<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Document;
use App\Models\VehicleMaintenance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class NotificationPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (empty(config('app.key'))) {
            $this->artisan('key:generate');
        }
    }

    public function test_notification_counts_are_accurate_and_optimized()
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin'
        ]);

        $vehicle = Vehicle::create([
            'registration_number' => 'TEST-001',
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2020,
            'vehicle_type' => 'Saloon',
            'chassis_number' => 'CH-TEST-001',
            'status' => 'active'
        ]);

        // 1. Expiring Document (in 10 days)
        Document::create([
            'title' => 'Expiring Insurance',
            'vehicle_id' => $vehicle->id,
            'document_type' => 'insurance',
            'reference_number' => 'INS-001',
            'expiry_date' => Carbon::today()->addDays(10),
            'status' => 'active',
            'file_path' => 'documents/ins1.pdf',
            'file_name' => 'ins1.pdf',
            'file_type' => 'application/pdf',
            'extension' => 'pdf'
        ]);

        // 2. Expired Document (yesterday)
        Document::create([
            'title' => 'Expired Permit',
            'vehicle_id' => $vehicle->id,
            'document_type' => 'permit',
            'reference_number' => 'PER-001',
            'expiry_date' => Carbon::today()->subDay(),
            'status' => 'active',
            'file_path' => 'documents/per1.pdf',
            'file_name' => 'per1.pdf',
            'file_type' => 'application/pdf',
            'extension' => 'pdf'
        ]);

        // 3. Overdue Maintenance (scheduled for last week)
        VehicleMaintenance::create([
            'vehicle_id' => $vehicle->id,
            'maintenance_type' => 'Oil Change',
            'maintenance_date' => Carbon::today()->subWeek(),
            'status' => 'scheduled'
        ]);

        // 4. Upcoming Maintenance (scheduled for next week)
        VehicleMaintenance::create([
            'vehicle_id' => $vehicle->id,
            'maintenance_type' => 'Tire Rotation',
            'maintenance_date' => Carbon::today()->addWeek(),
            'status' => 'scheduled'
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($admin)->get('/notifications/ajax');

        $queries = DB::getQueryLog();

        // We expect exactly 2 main queries for stats (Documents and Maintenances)
        // plus potential queries for auth and session.
        // Before optimization there were 6 distinct count queries just for the stats.

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'notifications' => [
                    'documents_expiring' => 1,
                    'maintenance_overdue' => 1,
                    'maintenance_upcoming' => 1,
                    'insurance_expiring' => 1,
                    'expired_documents' => 1,
                ]
            ]);

        // Total queries should be low.
        // 1. Session load
        // 2. User load
        // 3. Document stats
        // 4. Maintenance stats
        // (and maybe some others depending on middleware)
        $this->assertLessThanOrEqual(10, count($queries), "Query count is too high: " . count($queries));
    }
}
