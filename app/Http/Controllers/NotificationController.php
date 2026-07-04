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
        $today = Carbon::today()->toDateString();
        $thirtyDaysFromNow = Carbon::today()->addDays(30)->toDateString();
        
        // Bolt: Consolidate multiple Document count queries into one for better performance
        $docStats = \DB::table('documents')
            ->where('status', 'active')
            ->selectRaw("
                COUNT(CASE WHEN expiry_date >= ? AND expiry_date <= ? THEN 1 END) as expiring,
                COUNT(CASE WHEN document_type = 'insurance' AND expiry_date >= ? AND expiry_date <= ? THEN 1 END) as insurance_expiring,
                COUNT(CASE WHEN expiry_date < ? THEN 1 END) as expired
            ", [$today, $thirtyDaysFromNow, $today, $thirtyDaysFromNow, $today])
            ->first();
        
        // Bolt: Consolidate Maintenance queries and fix non-existent column 'scheduled_date' to 'maintenance_date'
        $maintenanceStats = \DB::table('vehicle_maintenances')
            ->where('status', '!=', 'completed')
            ->where('status', '!=', 'deleted')
            ->selectRaw("
                COUNT(CASE WHEN maintenance_date < ? THEN 1 END) as overdue,
                COUNT(CASE WHEN maintenance_date >= ? AND maintenance_date <= ? THEN 1 END) as upcoming
            ", [$today, $today, $thirtyDaysFromNow])
            ->first();
        
        return [
            'documents_expiring' => (int)($docStats->expiring ?? 0),
            'maintenance_overdue' => (int)($maintenanceStats->overdue ?? 0),
            'maintenance_upcoming' => (int)($maintenanceStats->upcoming ?? 0),
            'insurance_expiring' => (int)($docStats->insurance_expiring ?? 0),
            'low_fuel' => 0, // TODO: Implement once fuelManagement relation and fuel level tracking are fully integrated
            'expired_documents' => (int)($docStats->expired ?? 0),
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