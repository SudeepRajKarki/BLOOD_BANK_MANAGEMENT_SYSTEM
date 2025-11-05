<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChurnPrediction;
use App\Models\User;

class ChurnPredictionsSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        foreach ($users as $u) {
            ChurnPrediction::create([
                'user_id' => $u->id,
                'likelihood_score' => rand(0,100)/100,
                'prediction_date' => now()->toDateString(),
            ]);
        }
    }
}
