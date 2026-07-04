<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class FuelLog extends Model
{
    use Auditable, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        "company_id",
        'vehicle_id',
        'date',
        'odometer',
        'fuel_quantity',
        'fuel_cost',
        'fuel_price_per_unit',
        'fuel_type',
        'fuel_station',
        'location',
        'receipt_number',
        'driver_id',
        'logged_by',
        'notes',
        'is_full_tank',
        'is_maintenance_fuel',
        'payment_method',
        'status',
        'distance_traveled',
        'fuel_efficiency',
        'cost_per_distance',
        'deleted_by',
    ];

    protected $casts = [
        'date' => 'date',
        'odometer' => 'decimal:2',
        'fuel_quantity' => 'decimal:3',
        'fuel_cost' => 'decimal:2',
        'fuel_price_per_unit' => 'decimal:3',
        'is_full_tank' => 'boolean',
        'is_maintenance_fuel' => 'boolean',
    ];

    // Relationships
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function loggedBy()
    {
        return $this->belongsTo(User::class, 'logged_by');
    }

    // Scopes
    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['vehicle_id'] ?? null, function ($query, $vehicleId) {
            $query->where('vehicle_id', $vehicleId);
        })->when($filters['date_from'] ?? null, function ($query, $dateFrom) {
            $query->where('date', '>=', $dateFrom);
        })->when($filters['date_to'] ?? null, function ($query, $dateTo) {
            $query->where('date', '<=', $dateTo);
        })->when($filters['fuel_type'] ?? null, function ($query, $fuelType) {
            $query->where('fuel_type', $fuelType);
        })->when($filters['status'] ?? null, function ($query, $status) {
            $query->where('status', $status);
        })->when($filters['driver_id'] ?? null, function ($query, $driverId) {
            $query->where('driver_id', $driverId);
        });
    }

    // Accessors
    public function getFuelUnitAttribute()
    {
        return config('fuel.units.' . $this->fuel_type, 'liters');
    }

    public function getEfficiencyUnitAttribute()
    {
        return config('fuel.efficiency_units.' . $this->fuel_type, 'km/l');
    }

    // Methods
    public function calculateFuelEfficiency()
    {
        if ($this->distance_traveled > 0 && $this->fuel_quantity > 0) {
            return $this->distance_traveled / $this->fuel_quantity;
        }
        return null;
    }

    public function calculateCostPerDistance()
    {
        if ($this->distance_traveled > 0) {
            return $this->fuel_cost / $this->distance_traveled;
        }
        return null;
    }
}
