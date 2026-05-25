<?php
// database/seeders/MaintenanceChecklistSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MaintenanceChecklistItem;

class MaintenanceChecklistSeeder extends Seeder
{
    public function run()
    {
        $items = [
            // Engine
            ['category' => 'engine', 'item_name' => 'Engine Oil Change', 'item_code' => 'ENG-001', 'default_cost' => 250, 'estimated_hours' => 1.0, 'display_order' => 1],
            ['category' => 'engine', 'item_name' => 'Oil Filter Replacement', 'item_code' => 'ENG-002', 'default_cost' => 50, 'estimated_hours' => 0.5, 'display_order' => 2],
            ['category' => 'engine', 'item_name' => 'Air Filter Replacement', 'item_code' => 'ENG-003', 'default_cost' => 75, 'estimated_hours' => 0.5, 'display_order' => 3],
            ['category' => 'engine', 'item_name' => 'Fuel Filter Replacement', 'item_code' => 'ENG-004', 'default_cost' => 120, 'estimated_hours' => 1.0, 'display_order' => 4],
            ['category' => 'engine', 'item_name' => 'Spark Plugs Replacement', 'item_code' => 'ENG-005', 'default_cost' => 180, 'estimated_hours' => 1.5, 'display_order' => 5],
            
            // Transmission
            ['category' => 'transmission', 'item_name' => 'Transmission Fluid Change', 'item_code' => 'TRN-001', 'default_cost' => 300, 'estimated_hours' => 1.5, 'display_order' => 1],
            ['category' => 'transmission', 'item_name' => 'Transmission Filter', 'item_code' => 'TRN-002', 'default_cost' => 150, 'estimated_hours' => 1.0, 'display_order' => 2],
            
            // Brakes
            ['category' => 'brakes', 'item_name' => 'Brake Pad Replacement', 'item_code' => 'BRK-001', 'default_cost' => 350, 'estimated_hours' => 1.5, 'display_order' => 1],
            ['category' => 'brakes', 'item_name' => 'Brake Fluid Flush', 'item_code' => 'BRK-002', 'default_cost' => 120, 'estimated_hours' => 1.0, 'display_order' => 2],
            
            // Add more items as needed...
        ];
        
        foreach ($items as $item) {
            MaintenanceChecklistItem::create($item);
        }
    }
}