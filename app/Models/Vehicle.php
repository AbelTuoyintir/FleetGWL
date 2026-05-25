<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Traits\Auditable;

class Vehicle extends Model
{
    use Auditable, SoftDeletes;

    protected $fillable = [
        'registration_number',
        'make',
        'model',
        'year',
        'color',
        'chassis_number',
        'engine_number',
        'mileage',
        'registration_date',
        'insurance_expiry_date',
        'next_inspection_date',
        'fuel_consumption',
        'vehicle_type',
        'status',
        'notes',
        'owner_name',
        'owner_contact',
        'district_id',
        'region_id',
        'station_id',
        'assigned_driver_id',
        'photo',
        'purchase_price',
        'purchase_date',
        'created_by',
        'modified_by',
        'deleted_by',
    ];

    protected $casts = [
        'registration_date' => 'date',
        'insurance_expiry_date' => 'date',
        'next_inspection_date' => 'date',
        'purchase_date' => 'date',
        'fuel_consumption' => 'decimal:2',
        'purchase_price' => 'decimal:2',
    ];

    // ──────────────────────────── Relationships ────────────────────────────

    public function fuelLogs(): HasMany
    {
        return $this->hasMany(FuelLog::class);
    }

    public function mileageLogs(): HasMany
    {
        return $this->hasMany(MileageLog::class);
    }

    public function assignedDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'assigned_driver_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'assigned_driver_id');
    }
public function region()
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Get the district where the vehicle belongs.
     */
    public function district()
    {
        return $this->belongsTo(District::class);
    }

    /**
     * Get the station where the vehicle is based.
     */
    public function station()
    {
        return $this->belongsTo(Station::class);
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(Maintenance::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'vehicle_id');
    }

 
    public function isInMaintenance(): bool
    {
        return $this->status === 'maintenance';
    }

    /**
     * Check if next inspection is overdue.
     */
    public function isInspectionOverdue(): bool
    {
        return $this->next_inspection_date && $this->next_inspection_date->isPast();
    }

    /**
     * Check if insurance is expired.
     */
    public function isInsuranceExpired(): bool
    {
        return $this->insurance_expiry_date && $this->insurance_expiry_date->isPast();
    }

    /**
     * Get the latest maintenance record's next_expected_mileage.
     */
    public function getNextExpectedMileageAttribute()
    {
        $latest = $this->maintenances()
            ->whereNotNull('next_expected_mileage')
            ->latest()
            ->first();

        return $latest?->next_expected_mileage;
    }

    /**
     * Check if mileage-based maintenance is due based on the last maintenance record.
     */
    public function isMaintenanceDue(): bool
    {
        $nextMileage = $this->next_expected_mileage;

        if ($nextMileage && $this->mileage && $this->mileage >= $nextMileage) {
            return true;
        }

        return false;
    }

    /**
     * Get status badge class.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'active' => 'bg-emerald-100 text-emerald-700',
            'inactive' => 'bg-slate-100 text-slate-600',
            'maintenance' => 'bg-amber-100 text-amber-700',
            'disposed' => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-600',
        };
    }

    public function service(){
        return $this->hasMany(Service::class);
    }
}
