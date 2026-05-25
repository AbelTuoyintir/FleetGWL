<?php

namespace App\Models;
use App\Models\Asset;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\Auditable;

class Department extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'name',
        'created_by',
        'modified_by',
    ];

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modifiedBy()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }

    public function assets()
    {
        return $this->hasMany(Asset::class, 'department_id');
    }
}
