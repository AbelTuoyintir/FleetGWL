<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vehicle;
use App\Models\Document;
use App\Models\VehicleMaintenance;
use App\Models\FuelManagement;
use Carbon\Carbon;

class NotificationController extends Controller
{
    public function index()
    {
        // Get actual notification data from database
        $notifications = $this->getNotificationCounts();
        
        return view('notifications.index', [
            'notifications' => $notifications,
            'totalNotifications' => array_sum($notifications)
        ]);
    }
    
    /**
     * Get real-time notification counts from database
     */
    private function getNotificationCounts()
    {
        $today = Carbon::today();
        $thirtyDaysFromNow = Carbon::today()->addDays(30);

        // Bolt: Consolidate 6 database queries into 2 using conditional aggregation.
        // This significantly reduces database round-trips and fixes multiple bugs:
        // - Corrected non-existent column 'scheduled_date' to 'maintenance_date'
        // - Corrected non-existent column 'type' to 'document_type' on documents
        // - Safely handled non-existent 'fuelManagement' relationship

        $docStats = Document::where('status', 'active')
            ->selectRaw("
                COUNT(CASE WHEN expiry_date <= ? AND expiry_date >= ? THEN 1 END) as expiring,
                COUNT(CASE WHEN expiry_date < ? THEN 1 END) as expired,
                COUNT(CASE WHEN document_type = 'insurance' AND expiry_date <= ? AND expiry_date >= ? THEN 1 END) as insurance_expiring
            ", [$thirtyDaysFromNow, $today, $today, $thirtyDaysFromNow, $today])
            ->first();

        $maintenanceStats = VehicleMaintenance::where('status', '!=', 'completed')
            ->where('status', '!=', 'deleted')
            ->selectRaw("
                COUNT(CASE WHEN maintenance_date < ? THEN 1 END) as overdue,
                COUNT(CASE WHEN maintenance_date >= ? AND maintenance_date <= ? THEN 1 END) as upcoming
            ", [$today, $today, $thirtyDaysFromNow])
            ->first();
        
        // The 'fuelManagement' relationship doesn't exist on the Vehicle model.
        // Setting to 0 to prevent runtime errors until telemetry/fuel tracking is implemented.
        $lowFuelVehicles = 0;
        
        return [
            'documents_expiring' => (int) ($docStats->expiring ?? 0),
            'maintenance_overdue' => (int) ($maintenanceStats->overdue ?? 0),
            'maintenance_upcoming' => (int) ($maintenanceStats->upcoming ?? 0),
            'insurance_expiring' => (int) ($docStats->insurance_expiring ?? 0),
            'low_fuel' => $lowFuelVehicles,
            'expired_documents' => (int) ($docStats->expired ?? 0),
        ];
    }
    
    public static function getSharedNotifications()
    {
        $controller = new self();
        $notifications = $controller->getNotificationCounts();
        
        return [
            'notifications' => $notifications,
            'totalNotifications' => array_sum($notifications)
        ];
    }
    
    /**
     * API endpoint to get notifications for AJAX polling
     */
    public function getNotificationsAjax()
    {
        $notifications = $this->getNotificationCounts();
        
        return response()->json([
            'success' => true,
            'total' => array_sum($notifications),
            'notifications' => $notifications,
            'timestamp' => now()->toIso8601String()
        ]);
    }
    
    /**
     * Mark notifications as read (if you have a notification system)
     */
    public function markAsRead(Request $request)
    {
        // Implement your notification marking logic here
        // This depends on your notification system
        
        return response()->json(['success' => true]);
    }
}