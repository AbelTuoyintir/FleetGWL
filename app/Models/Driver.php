<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

use App\Traits\Auditable;

class Driver extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'user_id',
        'license_number',
        'license_expiry_date',
        'license_class',
        'license_photo',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'status',
        'notes',
        'created_by',
        'modified_by',
        'deleted_by',
    ];

    protected $appends = ['name', 'online_status'];

    public function getNameAttribute()
    {
        return $this->user ? $this->user->name : 'N/A';
    }

    /**
     * Proxy the online status from the User model.
     */
    public function getOnlineStatusAttribute()
    {
        return $this->user ? $this->user->online_status : 'offline';
    }

    protected $casts = [
        'license_expiry_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle()
    {
        return $this->hasOne(Vehicle::class, 'assigned_driver_id');
    }

    public function mileageLogs()
    {
        return $this->hasMany(MileageLog::class);
    }

    public function fuelLogs()
    {
        return $this->hasManyThrough(FuelLog::class, Vehicle::class, 'assigned_driver_id', 'vehicle_id');
    }

    public function maintenances()
    {
        return $this->hasManyThrough(Maintenance::class, Vehicle::class, 'assigned_driver_id', 'vehicle_id');
    }
}
