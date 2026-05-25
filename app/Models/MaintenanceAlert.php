<?php
// app/Models/MaintenanceAlert.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceAlert extends Model
{
    protected $table = 'maintenance_alerts';
    
    protected $fillable = [
        'vehicle_id',
        'driver_id',
        'current_mileage',
        'mileage_since_maintenance',
        'alert_type',
        'status',
        'sent_at',
        'acknowledged_at',
        'notes'
    ];
    
    protected $casts = [
        'sent_at' => 'datetime',
        'acknowledged_at' => 'datetime'
    ];
    
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
    
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
    
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
    
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }
}