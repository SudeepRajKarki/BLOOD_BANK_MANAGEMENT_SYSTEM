<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CampaignReport;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\User;

class CampaignReportsSeeder extends Seeder
{
    public function run(): void
    {
        $completedCampaigns = Campaign::where('status', 'Completed')->get();
        $admins = User::where('role', 'admin')->pluck('id')->toArray();

        if (empty($admins)) {
            return;
        }

        foreach ($completedCampaigns as $campaign) {
            // Check if report already exists
            $existingReport = CampaignReport::where('campaign_id', $campaign->id)->first();
            if ($existingReport) {
                continue; // Skip if report already exists
            }

            $donations = Donation::where('campaign_id', $campaign->id)->get();
            
            if ($donations->isEmpty()) {
                continue; // Skip campaigns with no donations
            }

            $totalQuantity = $donations->sum('quantity_ml');
            
            // Group donations by blood type
            $byType = $donations->groupBy('blood_type')->map(function ($group) {
                return $group->sum('quantity_ml');
            })->toArray();
            
            // Get unique donors
            $donors = $donations->map(function ($donation) {
                return [
                    'donor_id' => $donation->donor_id,
                    'name' => $donation->donor ? $donation->donor->name : 'Unknown',
                    'quantity_ml' => $donation->quantity_ml,
                    'blood_type' => $donation->blood_type,
                ];
            })->unique('donor_id')->values();
            
            // Build report text
            $reportLines = [];
            $reportLines[] = "Campaign #{$campaign->id} completed on {$campaign->date} at {$campaign->location}";
            $reportLines[] = "Total quantity collected: {$totalQuantity} ml";
            $reportLines[] = "Total donors: " . $donors->count();
            $reportLines[] = "";
            $reportLines[] = "Breakdown by blood type:";
            foreach ($byType as $bloodType => $quantity) {
                $reportLines[] = "  - {$bloodType}: {$quantity} ml";
            }
            $reportLines[] = "";
            $reportLines[] = "Donors participated:";
            foreach ($donors->take(20) as $donor) { // Limit to first 20 for readability
                $reportLines[] = "  - {$donor['name']} (ID: {$donor['donor_id']}) - {$donor['blood_type']} - {$donor['quantity_ml']} ml";
            }
            if ($donors->count() > 20) {
                $reportLines[] = "  ... and " . ($donors->count() - 20) . " more donors";
            }
            
            $reportText = implode("\n", $reportLines);
            
            // Create campaign report
            CampaignReport::create([
                'campaign_id' => $campaign->id,
                'report_text' => $reportText,
                'total_quantity_ml' => $totalQuantity,
                'by_type' => json_encode($byType),
                'donors' => json_encode($donors->toArray()),
                'created_by' => $admins[array_rand($admins)],
            ]);
        }
    }
}

