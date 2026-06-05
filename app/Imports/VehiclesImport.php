<?php
// app/Imports/VehiclesImport.php

namespace App\Imports;

use App\Models\Vehicle;
use App\Models\Region;
use App\Models\District;
use App\Models\Driver;
use App\Models\Office;
use App\Models\Station;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VehiclesImport implements ToCollection, WithHeadingRow, SkipsOnError
{
    use SkipsErrors;
    
    protected $results = [
        'success' => 0,
        'failed' => 0,
        'errors' => [],
        'total' => 0
    ];
    
    public function collection(Collection $rows)
    {
        $this->results['total'] = $rows->count();
        
        DB::beginTransaction();
        
        try {
            foreach ($rows as $index => $row) {
                try {
                    $this->importRow($row, $index);
                    $this->results['success']++;
                } catch (\Exception $e) {
                    $this->results['failed']++;
                    $this->results['errors'][] = [
                        'row' => $index + 2, // +2 because of heading row and zero index
                        'message' => $e->getMessage(),
                        'data' => $row->toArray()
                    ];
                }
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    protected function importRow($row, $index)
    {
        // Find or create region by name
        $regionId = null;
        if (!empty($row['region_name'])) {
            $region = Region::firstOrCreate(
                ['name' => $row['region_name']],
                ['status' => 'active', 'created_by' => auth()->id()]
            );
            $regionId = $region->id;
        }
        
        // Find district
        $districtId = null;
        if (!empty($row['district_name'])) {
            $district = District::where('name', $row['district_name'])
                ->when($regionId, function($q) use ($regionId) {
                    return $q->where('region_id', $regionId);
                })
                ->first();
            
            if ($district) {
                $districtId = $district->id;
            }
        }
        
        // Find station
        $stationId = null;
        if (!empty($row['station_name'])) {
            $station = Station::where('name', $row['station_name'])->first();
            if ($station) {
                $stationId = $station->id;
            }
        }
        
        // Find driver by email
        $driverId = null;
        if (!empty($row['assigned_driver_email'])) {
            $driver = Driver::where('email', $row['assigned_driver_email'])->first();
            if ($driver) {
                $driverId = $driver->id;
            }
        }
        
        // Find or create office if needed
        $officeId = null;
        if (!empty($row['office_name'])) {
            $office = Office::firstOrCreate(
                ['name' => $row['office_name']],
                ['status' => 'active']
            );
            $officeId = $office->id;
        }
        
        // Validate required fields
        if (empty($row['registration_number'])) {
            throw new \Exception('Registration number is required');
        }
        
        if (empty($row['make']) || empty($row['model'])) {
            throw new \Exception('Make and model are required');
        }
        
        if (empty($row['vehicle_type'])) {
            throw new \Exception('Vehicle type is required');
        }
        
        // Check if vehicle already exists
        $existingVehicle = Vehicle::where('registration_number', $row['registration_number'])
            ->orWhere('chassis_number', $row['chassis_number'] ?? '')
            ->first();
            
        if ($existingVehicle) {
            // Update existing vehicle
            $existingVehicle->update([
                'make' => $row['make'] ?? $existingVehicle->make,
                'model' => $row['model'] ?? $existingVehicle->model,
                'year' => $row['year'] ?? $existingVehicle->year,
                'color' => $row['color'] ?? $existingVehicle->color,
                'chassis_number' => $row['chassis_number'] ?? $existingVehicle->chassis_number,
                'engine_number' => $row['engine_number'] ?? $existingVehicle->engine_number,
                'mileage' => $row['mileage'] ?? $existingVehicle->mileage,
                'vehicle_type' => $row['vehicle_type'] ?? $existingVehicle->vehicle_type,
                'status' => $row['status'] ?? $existingVehicle->status,
                'region_id' => $regionId ?? $existingVehicle->region_id,
                'district_id' => $districtId ?? $existingVehicle->district_id,
                'station_id' => $stationId ?? $existingVehicle->station_id,
                'office_id' => $officeId ?? $existingVehicle->office_id,
                'assigned_driver_id' => $driverId ?? $existingVehicle->assigned_driver_id,
                'purchase_price' => $row['purchase_price'] ?? $existingVehicle->purchase_price,
                'purchase_date' => $row['purchase_date'] ?? $existingVehicle->purchase_date,
                'registration_date' => $row['registration_date'] ?? $existingVehicle->registration_date,
                'insurance_expiry_date' => $row['insurance_expiry_date'] ?? $existingVehicle->insurance_expiry_date,
                'next_inspection_date' => $row['next_inspection_date'] ?? $existingVehicle->next_inspection_date,
                'fuel_consumption' => $row['fuel_consumption'] ?? $existingVehicle->fuel_consumption,
                'owner_name' => $row['owner_name'] ?? $existingVehicle->owner_name,
                'owner_contact' => $row['owner_contact'] ?? $existingVehicle->owner_contact,
                'notes' => $row['notes'] ?? $existingVehicle->notes,
                'modified_by' => auth()->id(),
            ]);
        } else {
            // Create new vehicle
            Vehicle::create([
                'registration_number' => $row['registration_number'],
                'make' => $row['make'],
                'model' => $row['model'],
                'year' => $row['year'] ?? null,
                'color' => $row['color'] ?? null,
                'chassis_number' => $row['chassis_number'] ?? null,
                'engine_number' => $row['engine_number'] ?? null,
                'mileage' => $row['mileage'] ?? 0,
                'vehicle_type' => $row['vehicle_type'],
                'status' => $row['status'] ?? 'active',
                'region_id' => $regionId,
                'district_id' => $districtId,
                'station_id' => $stationId,
                'office_id' => $officeId,
                'assigned_driver_id' => $driverId,
                'purchase_price' => $row['purchase_price'] ?? null,
                'purchase_date' => $row['purchase_date'] ?? null,
                'registration_date' => $row['registration_date'] ?? null,
                'insurance_expiry_date' => $row['insurance_expiry_date'] ?? null,
                'next_inspection_date' => $row['next_inspection_date'] ?? null,
                'fuel_consumption' => $row['fuel_consumption'] ?? null,
                'owner_name' => $row['owner_name'] ?? null,
                'owner_contact' => $row['owner_contact'] ?? null,
                'notes' => $row['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);
        }
    }
    
    public function getResults()
    {
        return $this->results;
    }
}