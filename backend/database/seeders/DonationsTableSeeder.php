<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Donation;
use App\Models\User;
use App\Models\Campaign;
use App\Models\BloodRequest;
use Carbon\Carbon;

class DonationsTableSeeder extends Seeder
{
    public function run(): void
    {
        $donors = User::where('role', 'donor')->get();
        $campaigns = Campaign::all();
        $bloodRequests = BloodRequest::all();

        if ($donors->isEmpty()) {
            return;
        }

        // Create donations for completed campaigns (more donations)
        $completedCampaigns = $campaigns->where('status', 'Completed');
        foreach ($completedCampaigns as $campaign) {
            // Each completed campaign should have 10-30 donations
            $donationCount = rand(10, 30);
            $usedDonors = [];
            
            for ($i = 0; $i < $donationCount; $i++) {
                $donor = $donors->random();
                // Avoid duplicate donations from same donor in same campaign
                while (in_array($donor->id, $usedDonors)) {
                    $donor = $donors->random();
                }
                $usedDonors[] = $donor->id;

                $campaignDate = Carbon::parse($campaign->date);
                Donation::create([
                    'donor_id' => $donor->id,
                    'blood_type' => $donor->blood_type,
                    'quantity_ml' => rand(350, 500),
                    'donation_date' => $campaignDate->copy()->addHours(rand(8, 18))->toDateString(),
                    'campaign_id' => $campaign->id,
                    'location' => $campaign->location,
                    'request_id' => null,
                    'verified' => rand(0, 10) > 1, // 90% verified
                ]);

                // Update donor's last_donation_date
                $donor->last_donation_date = $campaignDate->toDateString();
                $donor->save();
            }
        }

        // Create donations for ongoing campaigns (fewer donations, recent)
        $ongoingCampaigns = $campaigns->where('status', 'Ongoing');
        foreach ($ongoingCampaigns as $campaign) {
            $donationCount = rand(3, 8);
            $usedDonors = [];
            $cutoffDate = Carbon::now()->subDays(56)->toDateString();
            
            // Filter eligible donors (56-day rule)
            $eligibleDonors = $donors->filter(function ($donor) use ($cutoffDate) {
                if (!$donor->last_donation_date) {
                    return true; // Never donated, eligible
                }
                $lastDonationDate = Carbon::parse($donor->last_donation_date)->toDateString();
                return $lastDonationDate <= $cutoffDate;
            });
            
            if ($eligibleDonors->isEmpty()) {
                continue; // Skip if no eligible donors
            }
            
            for ($i = 0; $i < $donationCount && $i < $eligibleDonors->count(); $i++) {
                $donor = $eligibleDonors->whereNotIn('id', $usedDonors)->random();
                $usedDonors[] = $donor->id;

                Donation::create([
                    'donor_id' => $donor->id,
                    'blood_type' => $donor->blood_type,
                    'quantity_ml' => rand(350, 500),
                    'donation_date' => Carbon::now()->subDays(rand(0, 2))->toDateString(),
                    'campaign_id' => $campaign->id,
                    'location' => $campaign->location,
                    'request_id' => null,
                    'verified' => false, // Ongoing campaigns may have unverified donations
                ]);
            }
        }

        // Create some request-based donations (donors who accepted requests)
        $acceptedRequests = BloodRequest::where('status', 'Approved')
            ->orWhereHas('donorMatches', function($query) {
                $query->where('status', 'Accepted');
            })
            ->get();

        foreach ($acceptedRequests as $request) {
            $matches = $request->donorMatches()->where('status', 'Accepted')->get();
            foreach ($matches as $match) {
                $donor = User::find($match->donor_id);
                if ($donor && $donor->role === 'donor') {
                    Donation::create([
                        'donor_id' => $donor->id,
                        'blood_type' => $request->blood_type,
                        'quantity_ml' => rand(350, min(500, $request->quantity_ml)),
                        'donation_date' => Carbon::parse($match->scheduled_at ?? now())->toDateString(),
                        'campaign_id' => null,
                        'location' => $match->scheduled_location ?? $request->location,
                        'request_id' => $request->id,
                        'verified' => rand(0, 5) > 1, // 80% verified
                    ]);
                }
            }
        }

        // Create some standalone donations (not from campaigns or requests)
        $standaloneCount = rand(5, 10);
        for ($i = 0; $i < $standaloneCount; $i++) {
            $donor = $donors->random();
            Donation::create([
                'donor_id' => $donor->id,
                'blood_type' => $donor->blood_type,
                'quantity_ml' => rand(350, 500),
                'donation_date' => Carbon::now()->subDays(rand(10, 60))->toDateString(),
                'campaign_id' => null,
                'location' => $donor->location,
                'request_id' => null,
                'verified' => true,
            ]);
        }
    }
}
