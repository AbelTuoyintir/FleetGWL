<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Document;
use App\Models\Vehicle;
use App\Models\VehicleMaintenance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\NotificationController;

class NotificationPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_correct_notification_counts()
    {
        // Insert documents directly to avoid missing factory issues
        \DB::table('documents')->insert([
            'title' => 'License',
            'slug' => 'license',
            'file_path' => '/tmp/test.pdf',
            'file_name' => 'test.pdf',
            'file_type' => 'application/pdf',
            'extension' => 'pdf',
            'status' => 'active',
            'expiry_date' => now()->addDays(10)->toDateString(),
            'document_type' => 'license',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('documents')->insert([
            'title' => 'Insurance',
            'slug' => 'insurance',
            'file_path' => '/tmp/test2.pdf',
            'file_name' => 'test2.pdf',
            'file_type' => 'application/pdf',
            'extension' => 'pdf',
            'status' => 'active',
            'expiry_date' => now()->addDays(15)->toDateString(),
            'document_type' => 'insurance',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('documents')->insert([
            'title' => 'Expired',
            'slug' => 'expired',
            'file_path' => '/tmp/test3.pdf',
            'file_name' => 'test3.pdf',
            'file_type' => 'application/pdf',
            'extension' => 'pdf',
            'status' => 'active',
            'expiry_date' => now()->subDays(5)->toDateString(),
            'document_type' => 'other',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create vehicle manually
        $vehicleId = \DB::table('vehicles')->insertGetId([
            'registration_number' => 'ABC-123',
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2020,
            'chassis_number' => 'CH123',
            'vehicle_type' => 'sedan',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('vehicle_maintenances')->insert([
            'vehicle_id' => $vehicleId,
            'maintenance_date' => now()->subDays(2)->toDateString(),
            'status' => 'scheduled',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('vehicle_maintenances')->insert([
            'vehicle_id' => $vehicleId,
            'maintenance_date' => now()->addDays(5)->toDateString(),
            'status' => 'scheduled',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $controller = new NotificationController();

        // Use reflection to access private method
        $reflection = new \ReflectionClass(NotificationController::class);
        $method = $reflection->getMethod('getNotificationCounts');
        $method->setAccessible(true);

        $counts = $method->invoke($controller);

        $this->assertEquals(2, $counts['documents_expiring']); // license + insurance
        $this->assertEquals(1, $counts['insurance_expiring']); // insurance only
        $this->assertEquals(1, $counts['expired_documents']); // expired other
        $this->assertEquals(1, $counts['maintenance_overdue']); // past maintenance_date
        $this->assertEquals(1, $counts['maintenance_upcoming']); // future maintenance_date
        $this->assertEquals(0, $counts['low_fuel']);
    }
}
