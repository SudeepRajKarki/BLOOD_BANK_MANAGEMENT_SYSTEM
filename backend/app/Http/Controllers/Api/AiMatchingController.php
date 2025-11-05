<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\Donation;
use App\Models\ChurnPrediction;
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
            $response = Http::timeout($timeout)->post($aiUrl . '/demand-forecast', $request->all());
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
            $response = Http::timeout($timeout)->post($aiUrl . '/campaign-targeting', $request->all());
            if ($response->successful()) {
                return response()->json($response->json());
            }
            return response()->json(['error' => 'AI service error', 'status' => $response->status()], 502);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not connect to AI service', 'message' => $e->getMessage()], 502);
        }
    }
}
