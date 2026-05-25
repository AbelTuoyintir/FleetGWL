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
        
        // Documents expiring within 30 days (Insurance & other docs)
        $documentsExpiring = Document::where('expiry_date', '<=', $thirtyDaysFromNow)
            ->where('expiry_date', '>=', $today)
            ->where('status', 'active')
            ->count();
        
        // Maintenance overdue (past due date)
        $maintenanceOverdue = VehicleMaintenance::where('scheduled_date', '<', $today)
            ->where('status', '!=', 'completed')
            ->count();
        
        // Upcoming maintenance (next 30 days)
        $maintenanceUpcoming = VehicleMaintenance::where('scheduled_date', '>=', $today)
            ->where('scheduled_date', '<=', $thirtyDaysFromNow)
            ->where('status', '!=', 'completed')
            ->count();
        
        // Insurance expiring within 30 days (if you have insurance table)
        $insuranceExpiring = Document::where('type', 'insurance')
            ->where('expiry_date', '<=', $thirtyDaysFromNow)
            ->where('expiry_date', '>=', $today)
            ->where('status', 'active')
            ->count();
        
        // Low fuel vehicles (fuel level below 15% or 20 liters)
        $lowFuelVehicles = Vehicle::whereHas('fuelManagement', function($query) {
            $query->where('current_fuel_level', '<', 15) // percentage
                  ->orWhere('current_fuel_liters', '<', 20);
        })->count();
        
        // Alternative: If you have fuel level in vehicles table
        // $lowFuelVehicles = Vehicle::where('fuel_level_percentage', '<', 15)->count();
        
        // Expired documents (overdue)
        $expiredDocuments = Document::where('expiry_date', '<', $today)
            ->where('status', 'active')
            ->count();
        
        return [
            'documents_expiring' => $documentsExpiring,
            'maintenance_overdue' => $maintenanceOverdue,
            'maintenance_upcoming' => $maintenanceUpcoming,
            'insurance_expiring' => $insuranceExpiring,
            'low_fuel' => $lowFuelVehicles,
            'expired_documents' => $expiredDocuments,
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