{{-- resources/views/driver/fuel-mileage/reports.blade.php --}}
@extends('layouts.driver')

@section('title', 'Reports - Fuel & Mileage')

@section('content')
<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Reports</h1>
                <p class="text-gray-500 text-sm mt-1">Maintenance and mileage analytics for your assigned vehicle.</p>
            </div>
            <a href="{{ route('driver.fuel-mileage.dashboard') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>

        {{-- Monthly Maintenance Expenditure --}}
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                <h2 class="font-semibold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-tools text-blue-600"></i>
                    Monthly Maintenance Expenditure
                </h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Month</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Requests</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">Total Cost (GHS)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($monthlyMaintenance as $row)
                            @php
                                $monthNum = (int)($row->month ?? 0);
                                $yearNum = (int)($row->year ?? 0);
                                $monthName = $monthNum >= 1 && $monthNum <= 12
                                    ? \Carbon\Carbon::create()->month($monthNum)->format('M')
                                    : ($row->month ?? '');
                                $label = $monthName && $yearNum ? ($monthName . ' ' . $yearNum) : '';
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-800">{{ $label ?: ($row->month . '/' . $row->year) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-800">{{ $row->request_count ?? 0 }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900 text-right">GHS {{ number_format($row->total_cost ?? 0, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-10 text-center text-gray-500">No completed maintenance records yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Weekly Mileage --}}
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                <h2 class="font-semibold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-road text-green-600"></i>
                    Weekly Mileage
                </h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Week</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Start Date</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">Distance (km)</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500">Alert</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($weeklyMileage as $log)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-800">{{ $log->week_label }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ isset($log->week_start_date) ? 
                                        
                                        (is_string($log->week_start_date)
                                            ? 
                                            \Carbon\Carbon::parse($log->week_start_date)->format('M d, Y')
                                            : $log->week_start_date->format('M d, Y'))
                                        : '' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 text-right">{{ number_format($log->distance_covered ?? 0) }}</td>
                                <td class="px-6 py-4 text-sm text-center">
                                    @if(!empty($log->service_alert))
                                        <span class="inline-flex items-center gap-2 px-2 py-1 rounded-full text-xs bg-red-100 text-red-700">
                                            <i class="fas fa-bell"></i>
                                            Alert
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-2 px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-600">
                                            <i class="fas fa-check"></i>
                                            OK
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-gray-500">No mileage logs yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Maintenance Type Breakdown --}}
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                <h2 class="font-semibold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-chart-pie text-purple-600"></i>
                    Maintenance Type Breakdown
                </h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Type</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">Count</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">Total Cost (GHS)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($typeBreakdown as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-800">{{ ucfirst($row->maintenance_type ?? '') }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900 text-right">{{ $row->count ?? 0 }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900 text-right">GHS {{ number_format($row->total_cost ?? 0, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-10 text-center text-gray-500">No maintenance records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

