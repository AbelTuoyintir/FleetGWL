<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'maintenance_id',
        'name',
        'cost',
        'description',
    ];

    /**
     * Get the maintenance record associated with this service.
     */
    public function maintenance()
    {
        return $this->belongsTo(Maintenance::class);
    }
}