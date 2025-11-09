<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChurnPrediction;
use App\Models\User;
use App\Models\Donation;
use Carbon\Carbon;

class ChurnPredictionsSeeder extends Seeder
{
    public function run(): void
    {
        $donors = User::where('role', 'donor')->get();

        foreach ($donors as $donor) {
            // Calculate realistic churn probability based on donor behavior
            $lastDonationDays = null;
            if ($donor->last_donation_date) {
                $lastDonationDays = Carbon::parse($donor->last_donation_date)->diffInDays(now());
            }
            
            $donationCount = Donation::where('donor_id', $donor->id)->count();
            
            // Calculate churn probability based on factors:
            // 1. Days since last donation (longer = higher churn)
            // 2. Total donations (fewer = higher churn)
            // 3. Response rate (estimated based on donation frequency)
            
            $churnScore = 0.3; // Base churn probability
            
            // Factor 1: Last donation days
            if ($lastDonationDays !== null) {
                if ($lastDonationDays > 180) {
                    $churnScore += 0.4; // Very high churn risk
                } elseif ($lastDonationDays > 120) {
                    $churnScore += 0.3; // High churn risk
                } elseif ($lastDonationDays > 90) {
                    $churnScore += 0.2; // Moderate churn risk
                } elseif ($lastDonationDays > 60) {
                    $churnScore += 0.1; // Low churn risk
                } else {
                    $churnScore -= 0.1; // Very low churn (recent donor)
                }
            } else {
                // No donation history
                if ($donationCount === 0) {
                    $churnScore += 0.2; // New donor, uncertain
                }
            }
            
            // Factor 2: Donation count (more donations = lower churn)
            if ($donationCount > 10) {
                $churnScore -= 0.3; // Very loyal donor
            } elseif ($donationCount > 5) {
                $churnScore -= 0.2; // Loyal donor
            } elseif ($donationCount > 2) {
                $churnScore -= 0.1; // Regular donor
            } elseif ($donationCount === 0) {
                $churnScore += 0.1; // Never donated, higher churn
            }
            
            // Factor 3: Health status
            if ($donor->health_status !== 'Good') {
                $churnScore += 0.1; // Health issues may prevent donation
            }
            
            // Normalize churn score to 0-1 range
            $churnScore = max(0.0, min(1.0, $churnScore));
            
            // Create multiple predictions over time (latest one is most important)
            $predictionDates = [
                now()->subDays(30),
                now()->subDays(15),
                now(),
            ];
            
            foreach ($predictionDates as $predictionDate) {
                // Add some variation to predictions over time
                $variation = (rand(-10, 10) / 100);
                $likelihoodScore = max(0.0, min(1.0, $churnScore + $variation));
                
                ChurnPrediction::updateOrCreate(
                    [
                        'user_id' => $donor->id,
                        'prediction_date' => $predictionDate->toDateString(),
                    ],
                    [
                        'likelihood_score' => round($likelihoodScore, 2),
                    ]
                );
            }
        }
    }
}
