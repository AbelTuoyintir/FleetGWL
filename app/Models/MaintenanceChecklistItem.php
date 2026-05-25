<?php
// app/Models/MaintenanceChecklistItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceChecklistItem extends Model
{
    protected $fillable = [
        'category', 'item_name', 'item_code', 'description', 
        'estimated_hours', 'default_cost', 'is_active', 'display_order'
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'estimated_hours' => 'decimal:2',
        'default_cost' => 'decimal:2'
    ];
    
    public static function getCategories()
    {
        return [
            'engine' => ['name' => 'Engine Services', 'icon' => 'fa-engine', 'color' => 'red'],
            'transmission' => ['name' => 'Transmission & Drivetrain', 'icon' => 'fa-cogs', 'color' => 'blue'],
            'brakes' => ['name' => 'Brake System', 'icon' => 'fa-brake-warning', 'color' => 'orange'],
            'electrical' => ['name' => 'Electrical System', 'icon' => 'fa-bolt', 'color' => 'yellow'],
            'cooling' => ['name' => 'Cooling System', 'icon' => 'fa-temperature-low', 'color' => 'cyan'],
            'tires' => ['name' => 'Tires & Wheels', 'icon' => 'fa-circle', 'color' => 'gray'],
            'suspension' => ['name' => 'Suspension & Steering', 'icon' => 'fa-car-side', 'color' => 'indigo'],
            'exhaust' => ['name' => 'Exhaust System', 'icon' => 'fa-smog', 'color' => 'green'],
            'body' => ['name' => 'Body & Interior', 'icon' => 'fa-car', 'color' => 'pink'],
            'fluids' => ['name' => 'Fluids & Filters', 'icon' => 'fa-oil-can', 'color' => 'teal'],
        ];
    }
}