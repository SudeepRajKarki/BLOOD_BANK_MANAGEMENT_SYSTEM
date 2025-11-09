<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BloodInventory;

class BloodInventorySeeder extends Seeder
{
    public function run(): void
    {
        $types = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
        
        // Realistic inventory levels (in ml)
        // Common blood types have more inventory
        $inventoryLevels = [
            'O+' => rand(15000, 25000),   // Most common
            'A+' => rand(12000, 20000),   // Very common
            'B+' => rand(10000, 18000),   // Common
            'AB+' => rand(3000, 8000),    // Rare
            'O-' => rand(5000, 12000),    // Universal donor, important
            'A-' => rand(4000, 10000),    // Less common
            'B-' => rand(3000, 8000),     // Less common
            'AB-' => rand(500, 3000),     // Rarest
        ];

        foreach ($types as $type) {
            // Check if inventory already exists
            $existing = BloodInventory::where('blood_type', $type)
                ->where('location', 'Central Bank')
                ->first();

            if ($existing) {
                // Update existing inventory
                $existing->quantity_ml = $inventoryLevels[$type];
                $existing->save();
            } else {
                // Create new inventory
                BloodInventory::create([
                    'blood_type' => $type,
                    'quantity_ml' => $inventoryLevels[$type],
                    'location' => 'Central Bank',
                ]);
            }
        }

        // Create some inventory records for other locations (optional)
        $otherLocations = ['Regional Bank 1', 'Regional Bank 2'];
        foreach ($otherLocations as $location) {
            foreach ($types as $type) {
                if (rand(0, 1)) { // 50% chance
                    BloodInventory::create([
                        'blood_type' => $type,
                        'quantity_ml' => rand(1000, 5000),
                        'location' => $location,
                    ]);
                }
            }
        }
    }
}
