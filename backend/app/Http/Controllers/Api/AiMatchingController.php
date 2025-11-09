<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\Donation;
use App\Models\ChurnPrediction;
use App\Models\BloodRequest;
use App\Models\Campaign;
use Carbon\Carbon;

class AiMatchingController extends Controller
{
    // Donor matching using k-NN
    public function matchDonor(Request $request)
    {
        $aiUrl = config('ai.url', 'http://127.0.0.1:5000');
        $timeout = config('ai.timeout', 5);
        try {
            // Gather donors from DB and include as payload to AI to avoid AI needing direct DB access
            $bloodType = $request->input('blood_type');
            $location = $request->input('location');
            $donorsQuery = User::where('role', 'donor');
            if ($bloodType) $donorsQuery->where('blood_group', $bloodType);
            $donors = $donorsQuery->get()->map(function ($d) {
                // enrich donor payload for AI: include last_donation_days, response_rate (from churn prediction), and total donations
                $lastDonationDays = null;
                if ($d->last_donation_date) {
                    try {
                        $lastDonationDays = Carbon::parse($d->last_donation_date)->diffInDays(now());
                    } catch (\Exception $e) {
                        $lastDonationDays = null;
                    }
                }

                $churn = ChurnPrediction::where('user_id', $d->id)->latest('prediction_date')->first();
                $responseRate = $churn ? ($churn->likelihood_score ?? null) : null;

                $donationCount = Donation::where('donor_id', $d->id)->count();

                return [
                    'id' => $d->id,
                    'name' => $d->name,
                    'email' => $d->email,
                    'location' => $d->location,
                    'blood_group' => $d->blood_group,
                    'last_donation_date' => $d->last_donation_date,
                    'last_donation_days' => $lastDonationDays,
                    'response_rate' => $responseRate,
                    'total_donations' => $donationCount,
                ];
            })->toArray();

            $payload = array_merge($request->all(), ['donors' => $donors]);
            $response = Http::timeout($timeout)->post($aiUrl . '/match-donor', $payload);
            if ($response->successful()) {
                return response()->json($response->json());
            }
            return response()->json(['error' => 'AI service error', 'status' => $response->status()], 502);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not connect to AI service', 'message' => $e->getMessage()], 502);
        }
    }

    // Priority prediction using NLP
    public function predictPriority(Request $request)
    {
        $aiUrl = config('ai.url', 'http://127.0.0.1:5000');
        $timeout = config('ai.timeout', 5);
        try {
            $response = Http::timeout($timeout)->post($aiUrl . '/predict-priority', $request->all());
            if ($response->successful()) {
                return response()->json($response->json());
            }
            return response()->json(['error' => 'AI service error', 'status' => $response->status()], 502);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not connect to AI service', 'message' => $e->getMessage()], 502);
        }
    }

    // Churn prediction
    public function predictChurn(Request $request)
    {
        $aiUrl = config('ai.url', 'http://127.0.0.1:5000');
        $timeout = config('ai.timeout', 5);
        try {
            $response = Http::timeout($timeout)->post($aiUrl . '/churn-predict', $request->all());
            if ($response->successful()) {
                return response()->json($response->json());
            }
            return response()->json(['error' => 'AI service error', 'status' => $response->status()], 502);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not connect to AI service', 'message' => $e->getMessage()], 502);
        }
    }

    // Demand forecasting
    public function forecastDemand(Request $request)
    {
        $aiUrl = config('ai.url', 'http://127.0.0.1:5000');
        $timeout = config('ai.timeout', 10);
        try {
            // Get blood request history from database for demand forecasting
            $bloodRequests = \App\Models\BloodRequest::selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(quantity_ml) as total_ml')
                ->where('created_at', '>=', now()->subDays(100))
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get();

            // Format data for AI (prophet expects 'ds' for date and 'y' for value)
            $history = [];
            foreach ($bloodRequests as $req) {
                $history[] = [
                    'ds' => $req->date,
                    'y' => (float) $req->count, // Use request count as demand indicator
                ];
            }

            // If we have less than 30 days of data, generate some historical data points
            if (count($history) < 30) {
                // Fill in missing dates with estimated values based on existing data
                $avgDemand = count($history) > 0 ? collect($history)->avg('y') : 5;
                $startDate = Carbon::now()->subDays(100);
                for ($i = 0; $i < 100; $i++) {
                    $date = $startDate->copy()->addDays($i)->format('Y-m-d');
                    $existing = collect($history)->firstWhere('ds', $date);
                    if (!$existing) {
                        // Add some variation
                        $variation = $avgDemand * (0.8 + (rand(0, 40) / 100));
                        $history[] = [
                            'ds' => $date,
                            'y' => round($variation, 1),
                        ];
                    }
                }
                // Sort by date
                usort($history, function($a, $b) {
                    return strcmp($a['ds'], $b['ds']);
                });
            }

            $payload = [
                'history' => $history,
            ];

            $response = Http::timeout($timeout)->post($aiUrl . '/demand-forecast', $payload);
            if ($response->successful()) {
                return response()->json($response->json());
            }
            return response()->json(['error' => 'AI service error', 'status' => $response->status()], 502);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not connect to AI service', 'message' => $e->getMessage()], 502);
        }
    }

    // Campaign targeting / recommendation
    public function recommendCampaigns(Request $request)
    {
        $aiUrl = config('ai.url', 'http://127.0.0.1:5000');
        $timeout = config('ai.timeout', 10);
        try {
            // Get campaign data from database
            $campaigns = \App\Models\Campaign::with(['donations', 'creator'])
                ->where('status', 'Completed')
                ->orderBy('date', 'desc')
                ->get();

            $campaignData = [];
            foreach ($campaigns as $campaign) {
                $donations = $campaign->donations;
                $donationCount = $donations->count();
                $totalQuantity = $donations->sum('quantity_ml');

                // Calculate metrics for AI
                // Turnout rate: Based on expected vs actual donors (assume expected is based on location population or previous campaigns)
                // For simplicity, use donation count as a proxy (normalize to 0-100)
                $expectedDonors = 50; // Base expected donors (could be calculated from location data)
                $turnoutRate = min(100, ($donationCount / $expectedDonors) * 100);

                // Shortage reports: Based on blood requests from same location around campaign time
                $campaignDate = Carbon::parse($campaign->date);
                $shortageReports = BloodRequest::where('location', $campaign->location)
                    ->whereBetween('created_at', [
                        $campaignDate->copy()->subDays(7),
                        $campaignDate->copy()->addDays(7)
                    ])
                    ->where('status', 'Pending')
                    ->count();

                // Average wait minutes: Simulate based on donation count (more donations = longer waits)
                // Could be actual data if tracked, for now estimate
                $avgWaitMinutes = $donationCount > 0 ? min(60, 10 + ($donationCount * 2)) : 0;

                $campaignData[] = [
                    'location' => $campaign->location,
                    'turnout_rate' => round($turnoutRate, 1),
                    'shortage_reports' => $shortageReports,
                    'avg_wait_minutes' => round($avgWaitMinutes, 1),
                    'donation_count' => $donationCount,
                    'total_quantity_ml' => $totalQuantity,
                    'date' => $campaign->date,
                ];
            }

            // If no completed campaigns, use upcoming/ongoing campaigns with estimated metrics
            if (empty($campaignData)) {
                $allCampaigns = \App\Models\Campaign::all();
                foreach ($allCampaigns as $campaign) {
                    $donations = $campaign->donations;
                    $donationCount = $donations->count();
                    
                    $campaignData[] = [
                        'location' => $campaign->location,
                        'turnout_rate' => rand(40, 90), // Estimated
                        'shortage_reports' => rand(0, 5),
                        'avg_wait_minutes' => $donationCount > 0 ? min(60, 10 + ($donationCount * 2)) : rand(10, 30),
                        'donation_count' => $donationCount,
                        'total_quantity_ml' => $donations->sum('quantity_ml'),
                        'date' => $campaign->date,
                    ];
                }
            }

            $payload = [
                'campaigns' => $campaignData,
            ];

            $response = Http::timeout($timeout)->post($aiUrl . '/campaign-targeting', $payload);
            if ($response->successful()) {
                return response()->json($response->json());
            }
            return response()->json(['error' => 'AI service error', 'status' => $response->status()], 502);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not connect to AI service', 'message' => $e->getMessage()], 502);
        }
    }
}
