<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

use App\Traits\Auditable;

class Maintenance extends Model
{
    use HasFactory, Auditable;

    protected $table = 'vehicle_maintenances';
    protected static ?string $resolvedTable = null;

    public static function resolveTableName(): string
    {
        if (static::$resolvedTable !== null) {
            return static::$resolvedTable;
        }

        if (Schema::hasTable('vehicle_maintenances')) {
            return static::$resolvedTable = 'vehicle_maintenances';
        }

        if (Schema::hasTable('maintenances')) {
            return static::$resolvedTable = 'maintenances';
        }

        return static::$resolvedTable = 'vehicle_maintenances';
    }

    public function getTable()
    {
        return static::resolveTableName();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'vehicle_id',
        'driver_id',
        'maintenance_type',
        'other_maintenance_type',
        'checklist',
        'maintenance_date',
        'date',
        'mileage_at_service',
        'description',
        'parts_replaced',
        'cost',
        'service_provider',
        'next_service_due',
        'next_expected_mileage',
        'status',
        'attachments',
        'created_by',
        'modified_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'maintenance_date' => 'date',
        'date' => 'date',
        'next_service_due' => 'date',
        'mileage_at_service' => 'integer',
        'next_expected_mileage' => 'integer',
        'cost' => 'decimal:2',
        'checklist' => 'array',
        'attachments' => 'array',
    ];

    /**
     * The attributes with default values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'scheduled',
        'cost' => 0.00,
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['services'];

    /**
     * Get the vehicle for this maintenance record.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the driver for this maintenance record.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter by vehicle.
     */
    public function scopeVehicle($query, $vehicleId)
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    /**
     * Scope a query to get overdue maintenance.
     */
    public function scopeOverdue($query)
    {
        return $query->where('next_service_due', '<', now())
                     ->where('status', '!=', 'completed');
    }

    /**
     * Scope a query to get upcoming maintenance.
     */
    public function scopeUpcoming($query, $days = 30)
    {
        return $query->where('next_service_due', '>', now())
                     ->where('next_service_due', '<=', now()->addDays($days))
                     ->where('status', '!=', 'completed');
    }

    /**
     * Get the display date (use maintenance_date as primary).
     */
    public function getDisplayDateAttribute()
    {
        return $this->maintenance_date ?: $this->date;
    }

    /**
     * Get status badge HTML.
     */
    public function getStatusBadgeAttribute()
    {
        $statusColors = [
            'waiting' => 'bg-amber-100 text-amber-700 border border-amber-200',
            'dispatched' => 'bg-indigo-100 text-indigo-700 border border-indigo-200',
            'scheduled' => 'bg-blue-100 text-blue-700 border border-blue-200',
            'completed' => 'bg-emerald-100 text-emerald-700 border border-emerald-200',
            'cancelled' => 'bg-slate-100 text-slate-500 border border-slate-200',
        ];

        $statusLabels = [
            'waiting' => 'Pending Approval',
            'dispatched' => 'In Workshop',
            'scheduled' => 'Scheduled',
            'completed' => 'Finalized',
            'cancelled' => 'Cancelled',
        ];

        $color = $statusColors[$this->status] ?? 'bg-gray-50 text-gray-600 border border-gray-200';
        $label = $statusLabels[$this->status] ?? ucfirst($this->status);

        return '<span class="px-3 py-1 inline-flex text-[10px] leading-4 font-black rounded-full uppercase tracking-widest font-sans ' . $color . '">' .
               $label . '</span>';
    }

    /**
     * Check if maintenance is overdue.
     */
    public function getIsOverdueAttribute()
    {
        if (!$this->next_service_due || $this->status === 'completed') {
            return false;
        }

        return now()->greaterThan($this->next_service_due);
    }

    /**
     * Get days until next service is due.
     */
    public function getDaysUntilDueAttribute()
    {
        if (!$this->next_service_due) {
            return null;
        }

        return now()->diffInDays($this->next_service_due, false);
    }

    /**
     * Get formatted cost.
     */
    public function getFormattedCostAttribute()
    {
        return '$' . number_format($this->cost, 2);
    }

    /**
     * Get formatted mileage at service.
     */
    public function getFormattedMileageAttribute()
    {
        return number_format($this->mileage_at_service) . ' km';
    }

    /**
     * Get formatted next service mileage.
     */
    public function getFormattedNextServiceMileageAttribute()
    {
        return $this->next_expected_mileage ? number_format($this->next_expected_mileage) . ' km' : null;
    }

    /**
     * Get parts as array.
     */
    public function getPartsArrayAttribute()
    {
        if (!$this->parts_replaced) {
            return [];
        }

        return array_filter(array_map('trim', explode(',', $this->parts_replaced)));
    }

    /**
     * Get services as array (alias for parts - handles eager loading).
     */
    public function getServicesAttribute()
    {
        $parts = $this->parts_replaced;
        
        // Handle case where parts_replaced is a JSON string
        if (is_string($parts) && (str_starts_with($parts, '[') || str_starts_with($parts, '{'))) {
            $decoded = json_decode($parts, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        
        return $this->partsArray;
    }

    /**
     * Ensure checklist always returns an array for the frontend.
     */
    public function getChecklistAttribute($value)
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * Provide a default priority if none exists.
     */
    public function getPriorityAttribute()
    {
        return $this->attributes['priority'] ?? 'medium';
    }

    /**
     * Get attachments as array.
     */
    public function getAttachmentsArrayAttribute()
    {
        if (!$this->attachments) {
            return [];
        }

        return json_decode($this->attachments, true) ?? [];
    }

    /**
     * Boot method to handle events.
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically set the duplicate 'date' field to match maintenance_date
        static::saving(function ($model) {
            if ($model->maintenance_date && !$model->date) {
                $model->date = $model->maintenance_date;
            }
        });
    }
}
