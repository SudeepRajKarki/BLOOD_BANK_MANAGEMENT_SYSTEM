<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DonorMatch;
use App\Models\BloodRequest;
use App\Models\User;
use App\Models\BloodInventory;
use Carbon\Carbon;

class DonorMatchesSeeder extends Seeder
{
    public function run(): void
    {
        // Only create matches for requests that were sent to donors (inventory insufficient)
        $requests = BloodRequest::all();
        $donors = User::where('role', 'donor')->get();

        if ($requests->isEmpty() || $donors->isEmpty()) {
            return;
        }

        foreach ($requests as $request) {
            // Check if inventory was available when request was created
            $inventory = BloodInventory::where('blood_type', $request->blood_type)
                ->sum('quantity_ml');
            
            // Only create matches if inventory was insufficient (request sent to donors)
            if ($inventory < $request->quantity_ml) {
                // Get eligible donors (matching blood type and eligible by 56-day rule)
                $cutoffDate = Carbon::now()->subDays(56);
                $eligibleDonors = $donors->filter(function ($donor) use ($request, $cutoffDate) {
                    // Match blood type
                    if ($donor->blood_type !== $request->blood_type) {
                        return false;
                    }
                    
                    // Check eligibility (56-day rule)
                    if ($donor->last_donation_date) {
                        $lastDonationDate = Carbon::parse($donor->last_donation_date);
                        if ($lastDonationDate->greaterThanOrEqualTo($cutoffDate)) {
                            return false; // Not eligible
                        }
                    }
                    
                    return true;
                });

                if ($eligibleDonors->isEmpty()) {
                    continue;
                }

                // Create 2-5 matches per request
                $matchCount = min(rand(2, 5), $eligibleDonors->count());
                $selectedDonors = $eligibleDonors->random($matchCount);

                foreach ($selectedDonors as $donor) {
                    // Calculate match score based on various factors
                    $matchScore = 50; // Base score
                    
                    // Factor 1: Last donation days (optimal range: 56-180 days)
                    if ($donor->last_donation_date) {
                        $lastDonationDays = Carbon::parse($donor->last_donation_date)->diffInDays(now());
                        if ($lastDonationDays >= 56 && $lastDonationDays <= 180) {
                            $matchScore += 20;
                        } elseif ($lastDonationDays > 180) {
                            $matchScore += 10;
                        }
                    } else {
                        $matchScore += 15; // New donor
                    }
                    
                    // Factor 2: Location match
                    if ($donor->location === $request->location) {
                        $matchScore += 15;
                    }
                    
                    // Factor 3: Donation history (more donations = higher score)
                    $donationCount = \App\Models\Donation::where('donor_id', $donor->id)->count();
                    if ($donationCount > 5) {
                        $matchScore += 15;
                    } elseif ($donationCount > 2) {
                        $matchScore += 10;
                    } elseif ($donationCount > 0) {
                        $matchScore += 5;
                    }
                    
                    // Normalize score to 1-100
                    $matchScore = max(1, min(100, round($matchScore)));
                    
                    // Random status: mostly Pending, some Accepted, few Declined
                    $statusRand = rand(1, 10);
                    $status = 'Pending';
                    if ($statusRand <= 1) {
                        $status = 'Accepted';
                    } elseif ($statusRand <= 2) {
                        $status = 'Declined';
                    }

                    DonorMatch::create([
                        'request_id' => $request->id,
                        'donor_id' => $donor->id,
                        'match_score' => $matchScore,
                        'status' => $status,
                        'scheduled_at' => $status === 'Accepted' ? Carbon::now()->addDays(rand(1, 7)) : null,
                        'scheduled_location' => $status === 'Accepted' ? $request->location : null,
                    ]);
                }
            }
        }
    }
}
