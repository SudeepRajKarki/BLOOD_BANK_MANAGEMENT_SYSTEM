<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DonorMatch;
use App\Models\BloodRequest;
use App\Models\User;

class DonorMatchesSeeder extends Seeder
{
    public function run(): void
    {
        $requests = BloodRequest::all();
        $donors = User::where('role', 'donor')->pluck('id')->toArray();

        foreach ($requests as $req) {
            // create up to 3 matches
            $sample = (array) array_rand($donors, min(3, count($donors)));
            foreach ($sample as $idx) {
                DonorMatch::create([
                    'request_id' => $req->id,
                    'donor_id' => $donors[$idx],
                    'match_score' => rand(50, 100) / 100,
                ]);
            }
        }
    }
}
