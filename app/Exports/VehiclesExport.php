<?php
// app/Exports/VehiclesExport.php

namespace App\Exports;

use App\Models\Vehicle;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class VehiclesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $vehicles;
    
    public function __construct($vehicles)
    {
        $this->vehicles = $vehicles;
    }
    
    public function collection()
    {
        return $this->vehicles;
    }
    
    public function headings(): array
    {
        return [
            'Registration Number', 'Make', 'Model', 'Year', 'Color', 'Chassis Number',
            'Engine Number', 'Mileage', 'Vehicle Type', 'Status', 'Region',
            'District', 'Station', 'Assigned Driver', 'Driver Email',
            'Purchase Price (GHS)', 'Purchase Date', 'Registration Date',
            'Insurance Expiry Date', 'Next Inspection Date', 'Fuel Consumption (km/L)',
            'Owner Name', 'Owner Contact', 'Notes', 'Created At', 'Last Updated'
        ];
    }
    
    public function map($vehicle): array
    {
        return [
            $vehicle->registration_number,
            $vehicle->make,
            $vehicle->model,
            $vehicle->year,
            $vehicle->color,
            $vehicle->chassis_number,
            $vehicle->engine_number,
            $vehicle->mileage,
            $vehicle->vehicle_type,
            $vehicle->status,
            $vehicle->region->name ?? 'N/A',
            $vehicle->district->name ?? 'N/A',
            $vehicle->station->name ?? 'N/A',
            $vehicle->assignedDriver->name ?? 'Unassigned',
            $vehicle->assignedDriver->email ?? '',
            $vehicle->purchase_price,
            $vehicle->purchase_date,
            $vehicle->registration_date,
            $vehicle->insurance_expiry_date,
            $vehicle->next_inspection_date,
            $vehicle->fuel_consumption,
            $vehicle->owner_name,
            $vehicle->owner_contact,
            $vehicle->notes,
            $vehicle->created_at->format('Y-m-d H:i:s'),
            $vehicle->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}