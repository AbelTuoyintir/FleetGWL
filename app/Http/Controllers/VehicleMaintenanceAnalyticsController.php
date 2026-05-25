<?php

namespace App\Http\Controllers;

use App\Models\Maintenance;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VehicleMaintenanceAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        // Parse date range: Default to Year-to-date or last 12 months
        $period = $request->get('period', '12months');
        $dateRange = $this->getDateRange($period);

        // 1. Core KPIs
        $kpis = $this->getKpis($dateRange);

        // 2. Trend Data (Costs & Count over months)
        $monthlyTrend = $this->getMonthlyTrend($dateRange);

        // 3. Service Status Breakdown
        $statusBreakdown = $this->getStatusBreakdown($dateRange);

        // 4. Maintenance Type Breakdown
        $typeBreakdown = $this->getTypeBreakdown($dateRange);

        // 5. Top Costly Vehicles
        $topCostvehicles = $this->getTopCostlyVehicles($dateRange);

        return view('vehicle-maintenance.analytics', compact(
            'period',
            'dateRange',
            'kpis',
            'monthlyTrend',
            'statusBreakdown',
            'typeBreakdown',
            'topCostvehicles'
        ));
    }

    private function getDateRange($period)
    {
        return match ($period) {
            '30days' => ['start' => now()->subDays(30)->startOfDay(), 'end' => now()->endOfDay()],
            '3months' => ['start' => now()->subMonths(3)->startOfDay(), 'end' => now()->endOfDay()],
            'ytd' => ['start' => now()->startOfYear(), 'end' => now()->endOfDay()],
            default => ['start' => now()->subMonths(12)->startOfDay(), 'end' => now()->endOfDay()],
        };
    }

    private function getKpis($dateRange)
    {
        $baseQuery = Maintenance::where('status', '!=', 'deleted')
            ->whereBetween('maintenance_date', [$dateRange['start'], $dateRange['end']]);

        $totalServices = (clone $baseQuery)->count();
        $totalCost = (clone $baseQuery)->sum('cost');

        $completedServices = (clone $baseQuery)->where('status', 'completed')->count();
        $scheduledServices = (clone $baseQuery)->whereIn('status', ['scheduled', 'in_progress'])->count();

        return [
            'total_services'    => $totalServices,
            'total_cost'        => $totalCost,
            'completed_count'   => $completedServices,
            'scheduled_count'   => $scheduledServices,
        ];
    }

    private function getMonthlyTrend($dateRange)
    {
        $data = Maintenance::where('status', '!=', 'deleted')
            ->whereBetween('maintenance_date', [$dateRange['start'], $dateRange['end']])
            ->selectRaw('MONTH(maintenance_date) as month, COUNT(*) as count, SUM(cost) as cost')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $months = [];
        $counts = [];
        $costs = [];

        // Distribute standard 1-12 array format.
        for ($i = 1; $i <= 12; $i++) {
            $monthData = $data->where('month', $i)->first();
            $months[] = Carbon::create()->month($i)->format('M');
            $counts[] = $monthData ? $monthData->count : 0;
            $costs[] = $monthData ? $monthData->cost : 0;
        }

        return [
            'months' => $months,
            'counts' => $counts,
            'costs'  => $costs,
        ];
    }

    private function getStatusBreakdown($dateRange)
    {
        return Maintenance::where('status', '!=', 'deleted')
             ->whereBetween('maintenance_date', [$dateRange['start'], $dateRange['end']])
             ->select('status', DB::raw('COUNT(*) as count'))
             ->groupBy('status')
             ->get()
             ->pluck('count', 'status')
             ->toArray();
    }

    private function getTypeBreakdown($dateRange)
    {
        return Maintenance::where('status', '!=', 'deleted')
            ->whereBetween('maintenance_date', [$dateRange['start'], $dateRange['end']])
            ->select('maintenance_type', DB::raw('COUNT(*) as count'))
            ->groupBy('maintenance_type')
            ->get()
            ->pluck('count', 'maintenance_type')
            ->toArray();
    }

    private function getTopCostlyVehicles($dateRange)
    {
        return Maintenance::where('status', '!=', 'deleted')
            ->whereBetween('maintenance_date', [$dateRange['start'], $dateRange['end']])
            ->with('vehicle')
            ->select('vehicle_id', DB::raw('COUNT(*) as jobs_count'), DB::raw('SUM(cost) as total_cost'))
            ->groupBy('vehicle_id')
            ->orderByDesc('total_cost')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'plate_number' => $item->vehicle->registration_number ?? 'Unknown',
                    'make_model'   => trim(($item->vehicle->make ?? '') . ' ' . ($item->vehicle->model ?? 'Unknown')),
                    'jobs_count'   => $item->jobs_count,
                    'total_cost'   => $item->total_cost,
                ];
            });
    }
}
