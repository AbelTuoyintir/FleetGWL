<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class FuelStation extends Model
{
    use Auditable;

    protected $fillable = [
        'name',
        'phone',
        'fuel_types',
        'is_active',
    ];

    protected $casts = [
        'fuel_types' => 'array',
        'is_active' => 'boolean',
    ];

    public function fuelLogs()
    {
        return $this->hasMany(FuelLog::class, 'fuel_station', 'name');
    }
}
