<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DemandForecast;

class DemandForecastsSeeder extends Seeder
{
    public function run(): void
    {
        $types = ['A+','A-','B+','B-','O+','O-','AB+','AB-'];
        foreach ($types as $t) {
            DemandForecast::create([
                'blood_type' => $t,
                'location' => 'Central',
                'forecast_date' => now()->addDays(rand(1,30))->toDateString(),
                'predicted_units' => rand(10,100),
            ]);
        }
    }
}
