<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BloodInventory;

class BloodInventorySeeder extends Seeder
{
    public function run(): void
    {
        $types = ['A+','A-','B+','B-','O+','O-','AB+','AB-'];
        foreach ($types as $t) {
            BloodInventory::create([
                'blood_type' => $t,
                'quantity_ml' => rand(5000, 20000),
                'location' => 'Central Bank',
            ]);
        }
    }
}
