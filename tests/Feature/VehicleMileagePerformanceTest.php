<?php

namespace Tests\Feature;

use App\Models\FuelLog;
use App\Models\MileageLog;
use App\Models\Vehicle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VehicleMileagePerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_mileage_breakdown_performance()
    {
        // Setup
        $vehicle = Vehicle::create([
            'registration_number' => 'PERF-1',
            'make' => 'Toyota',
            'model' => 'Hilux',
            'year' => 2022,
            'status' => 'active',
            'vehicle_type' => 'Pickup',
            'chassis_number' => 'CH-PERF-1',
            'mileage' => 50000,
        ]);

        // Seed some data
        Carbon::setTestNow(Carbon::create(2026, 6, 28, 12, 0, 0));
        $now = Carbon::now();

        for ($i = 0; $i < 100; $i++) {
            $date = $now->copy()->subDays($i);
            MileageLog::create([
                'vehicle_id' => $vehicle->id,
                'start_mileage' => 10000 + ((100 - $i) * 100),
                'end_mileage' => 10000 + ((100 - $i) * 100) + 50,
                'created_at' => $date,
                'date' => $date->toDateString(),
            ]);

            FuelLog::create([
                'vehicle_id' => $vehicle->id,
                'date' => $date->toDateString(),
                'odometer' => 10000 + ((100 - $i) * 100) + 50,
                'fuel_quantity' => 10,
                'fuel_cost' => 100,
                'fuel_price_per_unit' => 10,
                'fuel_type' => 'petrol',
                'status' => 'recorded',
            ]);
        }

        // Original Implementation
        $startOriginal = microtime(true);
        $originalResult = $this->originalGetMileageBreakdown($vehicle);
        $endOriginal = microtime(true);
        $originalTime = ($endOriginal - $startOriginal) * 1000;

        // Optimized Implementation
        $startOptimized = microtime(true);
        $optimizedResult = $this->optimizedGetMileageBreakdown($vehicle);
        $endOptimized = microtime(true);
        $optimizedTime = ($endOptimized - $startOptimized) * 1000;

        dump("Original Breakdown Time: {$originalTime}ms");
        dump("Optimized Breakdown Time: {$optimizedTime}ms");

        // Verify results are same
        $this->assertEquals($originalResult, $optimizedResult);

        Carbon::setTestNow(); // Reset
    }

    private function originalGetMileageBreakdown($vehicle)
    {
        $periods = [
            [
                'label' => 'Last Week',
                'start' => Carbon::now()->subWeek()->startOfWeek(),
                'end' => Carbon::now()->subWeek()->endOfWeek(),
            ],
            [
                'label' => 'This Month',
                'start' => Carbon::now()->startOfMonth(),
                'end' => Carbon::now(),
            ],
            [
                'label' => 'Last Month',
                'start' => Carbon::now()->subMonth()->startOfMonth(),
                'end' => Carbon::now()->subMonth()->endOfMonth(),
            ],
            [
                'label' => 'Last 3 Months',
                'start' => Carbon::now()->subMonths(3)->startOfMonth(),
                'end' => Carbon::now(),
            ],
        ];

        $breakdown = [];

        foreach ($periods as $period) {
            // Get mileage logs for the period
            $distance = MileageLog::where('vehicle_id', $vehicle->id)
                ->whereBetween('created_at', [$period['start'], $period['end']])
                ->get()
                ->sum(function ($log) {
                    return max(0, ($log->end_mileage ?? 0) - ($log->start_mileage ?? 0));
                });

            // Get fuel logs for the period
            $fuelData = FuelLog::where('vehicle_id', $vehicle->id)
                ->whereBetween('date', [$period['start']->toDateString(), $period['end']->toDateString()])
                ->select(
                    DB::raw('SUM(fuel_quantity) as total_fuel'),
                    DB::raw('SUM(fuel_cost) as total_cost')
                )
                ->first();

            $breakdown[] = [
                'period' => $period['label'],
                'mileage' => $vehicle->mileage,
                'distance' => (float) $distance,
                'fuel_used' => (float) ($fuelData->total_fuel ?? 0),
                'cost' => (float) ($fuelData->total_cost ?? 0),
            ];
        }

        return $breakdown;
    }

    private function optimizedGetMileageBreakdown($vehicle)
    {
        $now = Carbon::now();
        $periods = [
            'Last Week' => [
                'start' => $now->copy()->subWeek()->startOfWeek(),
                'end' => $now->copy()->subWeek()->endOfWeek(),
            ],
            'This Month' => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy(),
            ],
            'Last Month' => [
                'start' => $now->copy()->subMonth()->startOfMonth(),
                'end' => $now->copy()->subMonth()->endOfMonth(),
            ],
            'Last 3 Months' => [
                'start' => $now->copy()->subMonths(3)->startOfMonth(),
                'end' => $now->copy(),
            ],
        ];

        // Bulk fetch mileage distance
        $mileageStats = MileageLog::where('vehicle_id', $vehicle->id)
            ->selectRaw("
                SUM(CASE WHEN created_at BETWEEN ? AND ? THEN CASE WHEN (COALESCE(end_mileage, 0) - COALESCE(start_mileage, 0)) > 0 THEN (COALESCE(end_mileage, 0) - COALESCE(start_mileage, 0)) ELSE 0 END ELSE 0 END) as last_week,
                SUM(CASE WHEN created_at BETWEEN ? AND ? THEN CASE WHEN (COALESCE(end_mileage, 0) - COALESCE(start_mileage, 0)) > 0 THEN (COALESCE(end_mileage, 0) - COALESCE(start_mileage, 0)) ELSE 0 END ELSE 0 END) as this_month,
                SUM(CASE WHEN created_at BETWEEN ? AND ? THEN CASE WHEN (COALESCE(end_mileage, 0) - COALESCE(start_mileage, 0)) > 0 THEN (COALESCE(end_mileage, 0) - COALESCE(start_mileage, 0)) ELSE 0 END ELSE 0 END) as last_month,
                SUM(CASE WHEN created_at BETWEEN ? AND ? THEN CASE WHEN (COALESCE(end_mileage, 0) - COALESCE(start_mileage, 0)) > 0 THEN (COALESCE(end_mileage, 0) - COALESCE(start_mileage, 0)) ELSE 0 END ELSE 0 END) as last_3_months
            ", [
                $periods['Last Week']['start'], $periods['Last Week']['end'],
                $periods['This Month']['start'], $periods['This Month']['end'],
                $periods['Last Month']['start'], $periods['Last Month']['end'],
                $periods['Last 3 Months']['start'], $periods['Last 3 Months']['end'],
            ])
            ->first();

        // Bulk fetch fuel stats
        $fuelStats = FuelLog::where('vehicle_id', $vehicle->id)
            ->selectRaw("
                SUM(CASE WHEN date BETWEEN ? AND ? THEN fuel_quantity ELSE 0 END) as last_week_fuel,
                SUM(CASE WHEN date BETWEEN ? AND ? THEN fuel_cost ELSE 0 END) as last_week_cost,
                SUM(CASE WHEN date BETWEEN ? AND ? THEN fuel_quantity ELSE 0 END) as this_month_fuel,
                SUM(CASE WHEN date BETWEEN ? AND ? THEN fuel_cost ELSE 0 END) as this_month_cost,
                SUM(CASE WHEN date BETWEEN ? AND ? THEN fuel_quantity ELSE 0 END) as last_month_fuel,
                SUM(CASE WHEN date BETWEEN ? AND ? THEN fuel_cost ELSE 0 END) as last_month_cost,
                SUM(CASE WHEN date BETWEEN ? AND ? THEN fuel_quantity ELSE 0 END) as last_3_months_fuel,
                SUM(CASE WHEN date BETWEEN ? AND ? THEN fuel_cost ELSE 0 END) as last_3_months_cost
            ", [
                $periods['Last Week']['start']->toDateString(), $periods['Last Week']['end']->toDateString(),
                $periods['Last Week']['start']->toDateString(), $periods['Last Week']['end']->toDateString(),
                $periods['This Month']['start']->toDateString(), $periods['This Month']['end']->toDateString(),
                $periods['This Month']['start']->toDateString(), $periods['This Month']['end']->toDateString(),
                $periods['Last Month']['start']->toDateString(), $periods['Last Month']['end']->toDateString(),
                $periods['Last Month']['start']->toDateString(), $periods['Last Month']['end']->toDateString(),
                $periods['Last 3 Months']['start']->toDateString(), $periods['Last 3 Months']['end']->toDateString(),
                $periods['Last 3 Months']['start']->toDateString(), $periods['Last 3 Months']['end']->toDateString(),
            ])
            ->first();

        $breakdown = [];
        $mapping = [
            'Last Week' => ['distance' => 'last_week', 'fuel' => 'last_week_fuel', 'cost' => 'last_week_cost'],
            'This Month' => ['distance' => 'this_month', 'fuel' => 'this_month_fuel', 'cost' => 'this_month_cost'],
            'Last Month' => ['distance' => 'last_month', 'fuel' => 'last_month_fuel', 'cost' => 'last_month_cost'],
            'Last 3 Months' => ['distance' => 'last_3_months', 'fuel' => 'last_3_months_fuel', 'cost' => 'last_3_months_cost'],
        ];

        foreach ($mapping as $label => $keys) {
            $breakdown[] = [
                'period' => $label,
                'mileage' => $vehicle->mileage,
                'distance' => (float) ($mileageStats->{$keys['distance']} ?? 0),
                'fuel_used' => (float) ($fuelStats->{$keys['fuel']} ?? 0),
                'cost' => (float) ($fuelStats->{$keys['cost']} ?? 0),
            ];
        }

        return $breakdown;
    }
}
