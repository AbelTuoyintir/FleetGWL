<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\Auditable;

class MileageLog extends Model
{
    use HasFactory, Auditable, BelongsToTenant;

    protected $fillable = [
        'vehicle_id',
        'driver_id',
        'start_mileage',
        'end_mileage',
        'date',
        'notes',
        'recorded_by',
        'service_alert',
        'created_by',
        'modified_by',
        'deleted_by',
    ];

    protected $casts = [
        'service_alert' => 'boolean',
    ];

    /**
     * Get the vehicle for this mileage log.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the driver for this mileage log.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get the distance covered (computed from start/end mileage).
     */
    public function getDistanceCoveredAttribute(): int
    {
        return max(0, ($this->end_mileage ?? 0) - ($this->start_mileage ?? 0));
    }

    /**
     * Get the user who recorded this mileage log.
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Auto-set service alert based on distance when saving.
     */
    public static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if ($model->start_mileage && $model->end_mileage) {
                $distance = $model->end_mileage - $model->start_mileage;
                // Set service alert if distance covered exceeds threshold (5000 km)
                $model->service_alert = $distance > 5000;
            }
        });
    }
}
