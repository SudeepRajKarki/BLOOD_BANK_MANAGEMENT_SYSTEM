<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Donation;
use App\Models\User;
use App\Models\Campaign;

class DonationsTableSeeder extends Seeder
{
    public function run(): void
    {
        $donors = User::where('role', 'donor')->pluck('id')->toArray();
        $campaignIds = Campaign::pluck('id')->toArray();

        foreach ($donors as $donorId) {
            Donation::create([
                'donor_id' => $donorId,
                'blood_type' => ['A+','B+','O+','AB+'][array_rand(['A+','B+','O+','AB+'])],
                'quantity_ml' => rand(200, 500),
                'donation_date' => now()->subDays(rand(0,30))->toDateString(),
                'campaign_id' => count($campaignIds) ? $campaignIds[array_rand($campaignIds)] : null,
                'location' => 'City ' . rand(1,5),
                'verified' => (bool) rand(0,1),
            ]);
        }
    }
}
