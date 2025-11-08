<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BloodRequest;
use App\Models\BloodInventory;
use App\Models\User;
use App\Models\DonorMatch;
use App\Models\Notification;
use App\Models\Donation;
use App\Models\ChurnPrediction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RequestController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if ($user->role !== 'receiver') {
            return response()->json(['error' => 'forbidden'], 403);
        }

        $requests = BloodRequest::where('receiver_id', $user->id)
            ->with('receiver')
            ->withCount('donorMatches')
            ->orderBy('created_at', 'desc')
            ->get();

        // Add notification status to each request
        $requests = $requests->map(function ($request) {
            $hasDonorMatches = $request->donor_matches_count > 0;
            $request->notification_sent_to = $hasDonorMatches ? 'donors' : 'admin';
            return $request;
        });

        return response()->json($requests);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'receiver') {
            return response()->json(['error' => 'forbidden'], 403);
        }

        $validated = $request->validate([
            'blood_type' => 'required|string|max:3',
            'quantity_ml' => 'required|integer|min:1',
            'reason' => 'required|string',
            'location' => 'nullable|string',
        ]);

        $quantity = $validated['quantity_ml'];
        $bloodType = $validated['blood_type'];
        $location = $validated['location'] ?? null;

        // ----------------------
        // STEP 1: Set priority using AI first (as per requirement)
        // ----------------------
        $priority = 'Medium'; // fallback
        $aiUrl = config('ai.url', 'http://127.0.0.1:5000');
        $timeout = config('ai.timeout', 5);

        try {
            $response = Http::timeout($timeout)->post($aiUrl . '/predict-priority', [
                'reason' => $validated['reason']
            ]);

            if ($response->successful()) {
                $priority = $response->json('priority') ?? 'Medium';
            }
        } catch (\Exception $e) {
            // fallback to Medium if AI service is unavailable
            Log::warning('AI priority prediction failed: ' . $e->getMessage());
        }

        // ----------------------
        // STEP 2: Check blood inventory availability
        // Note: All inventory is stored at "Central Bank" location
        // Request location is where blood is needed, not where inventory is stored
        // ----------------------
        $inventoryTotal = (int) BloodInventory::where('blood_type', $bloodType)
            ->sum('quantity_ml');
        $isInventoryAvailable = $inventoryTotal >= $quantity;

        // ----------------------
        // STEP 3: Create Blood Request
        // ----------------------
        $bloodRequest = BloodRequest::create([
            'receiver_id' => $user->id,
            'blood_type' => $bloodType,
            'quantity_ml' => $quantity,
            'reason' => $validated['reason'],
            'status' => 'Pending',
            'location' => $location,
            'priority' => $priority,
        ]);

        // ----------------------
        // STEP 4: Handle notifications based on inventory availability
        // ----------------------
        $matchedDonorsCount = 0;
        $matchedDonorsInfo = [];

        if ($isInventoryAvailable) {
            // Blood is available in inventory → Notify admin
            $this->notifyAdmins($bloodRequest, $inventoryTotal);
        } else {
            // Blood is NOT available in inventory → Use AI matching and notify donors
            $matchResult = $this->matchDonorsWithAI($bloodRequest);
            $matchedDonorsCount = $matchResult['count'] ?? 0;
            $matchedDonorsInfo = $matchResult['donors'] ?? [];
        }

        return response()->json([
            'request' => $bloodRequest,
            'inventory_available' => $isInventoryAvailable,
            'inventory_total_ml' => $inventoryTotal,
            'priority' => $priority,
            'matched_donors_count' => $matchedDonorsCount,
            'matched_donors' => $matchedDonorsInfo,
            'notification_sent_to' => $isInventoryAvailable ? 'admin' : 'donors',
            'message' => $isInventoryAvailable
                ? 'Request created. Admin has been notified for inventory approval.'
                : "Request created. {$matchedDonorsCount} donor(s) have been notified.",
        ], 201);
    }


    /**
     * Notify all admins about a new blood request that requires inventory approval
     */
    private function notifyAdmins(BloodRequest $bloodRequest, int $inventoryTotal)
    {
        $admins = User::where('role', 'admin')->get();

        $locationInfo = $bloodRequest->location
            ? " Request location: {$bloodRequest->location}."
            : "";

        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'message' => "New blood request #{$bloodRequest->id} for {$bloodRequest->blood_type} ({$bloodRequest->quantity_ml} ml) requires inventory approval. Priority: {$bloodRequest->priority}. Available inventory at Central Bank: {$inventoryTotal} ml.{$locationInfo}",
                'type' => 'request_approval',
                'is_read' => false,
            ]);
        }
    }

    /**
     * Match donors using AI and notify them about the blood request
     * Returns array with 'count' and 'donors' information
     */
    private function matchDonorsWithAI(BloodRequest $bloodRequest)
    {
        $aiUrl = config('ai.url', 'http://127.0.0.1:5000');
        $timeout = config('ai.timeout', 5);

        try {
            $averageDonationMl = 450;
            $neededDonors = max(1, ceil($bloodRequest->quantity_ml / $averageDonationMl));
            $maxDonors = $bloodRequest->priority === 'High' ? 15 : 10;
            $minDonors = $bloodRequest->priority === 'High' ? 3 : 2;
            $k = max($minDonors, min($neededDonors, $maxDonors));

            $donorsQuery = User::where('role', 'donor'); // Exclude non-donors
            if ($bloodRequest->blood_type) {
                $donorsQuery->where('blood_type', $bloodRequest->blood_type);
            }

            $donors = $donorsQuery->get()->map(function ($d) {
                $lastDonationDays = $d->last_donation_date ? Carbon::parse($d->last_donation_date)->diffInDays(now()) : null;
                $churn = ChurnPrediction::where('user_id', $d->id)->latest('prediction_date')->first();
                $responseRate = $churn ? ($churn->likelihood_score ?? null) : null;
                $donationCount = Donation::where('donor_id', $d->id)->count();

                return [
                    'id' => $d->id,
                    'name' => $d->name,
                    'email' => $d->email,
                    'location' => $d->location,
                    'blood_group' => $d->blood_type,
                    'last_donation_date' => $d->last_donation_date,
                    'last_donation_days' => $lastDonationDays,
                    'response_rate' => $responseRate,
                    'total_donations' => $donationCount,
                ];
            })->toArray();

            $response = Http::timeout($timeout)->post($aiUrl . '/match-donor', [
                'blood_type' => $bloodRequest->blood_type,
                'location' => $bloodRequest->location,
                'quantity_ml' => $bloodRequest->quantity_ml,
                'priority' => $bloodRequest->priority,
                'k' => $k,
                'donors' => $donors,
            ]);

            if ($response->successful()) {
                $matchedDonors = $response->json('nearest_donors') ?? [];
                if (empty($matchedDonors)) {
                    return $this->matchDonorsBasic($bloodRequest);
                }

                $matchedDonorsInfo = [];
                $notifiedCount = 0;

                foreach ($matchedDonors as $match) {
                    $donorId = $match['id'] ?? $match['donor_id'] ?? null;
                    if (!$donorId) continue;

                    $donor = User::find($donorId);
                    if (!$donor || $donor->role !== 'donor') continue; // Skip non-donors

                    $existingMatch = DonorMatch::where('request_id', $bloodRequest->id)
                        ->where('donor_id', $donorId)
                        ->first();
                    if ($existingMatch) continue;

                    $distanceKm = $match['distance_km'] ?? 0;
                    $matchScore = $distanceKm > 0 ? max(1, round(100 / ($distanceKm + 1))) : 1;

                    DonorMatch::create([
                        'request_id' => $bloodRequest->id,
                        'donor_id' => $donorId,
                        'match_score' => $matchScore,
                        'status' => 'Pending',
                    ]);

                    Notification::create([
                        'user_id' => $donorId,
                        'message' => "Urgent blood request #{$bloodRequest->id}: {$bloodRequest->blood_type} blood needed ({$bloodRequest->quantity_ml} ml). Priority: {$bloodRequest->priority}. You are a potential match!",
                        'type' => 'donation_request',
                        'is_read' => false,
                    ]);

                    $matchedDonorsInfo[] = [
                        'donor_id' => $donorId,
                        'name' => $donor->name ?? 'Unknown',
                        'distance_km' => round($distanceKm, 2),
                        'match_score' => $matchScore,
                        'location' => $donor->location ?? null,
                    ];
                    $notifiedCount++;
                }

                Log::info("Request #{$bloodRequest->id}: Matched {$notifiedCount} donors using AI matching (k={$k}, needed={$neededDonors}ml)");

                return [
                    'count' => $notifiedCount,
                    'donors' => $matchedDonorsInfo,
                    'method' => 'ai',
                ];
            }

            return $this->matchDonorsBasic($bloodRequest);
        } catch (\Exception $e) {
            Log::warning("Request #{$bloodRequest->id}: AI donor matching failed: " . $e->getMessage());
            return $this->matchDonorsBasic($bloodRequest);
        }
    }


    /**
     * Fallback: Basic donor matching without AI (matches by blood type)
     * Returns array with 'count' and 'donors' information
     * Calculates match scores based on donor history and eligibility
     */
    private function matchDonorsBasic(BloodRequest $bloodRequest)
    {
        $averageDonationMl = 450;
        $neededDonors = max(1, ceil($bloodRequest->quantity_ml / $averageDonationMl));
        $maxDonors = $bloodRequest->priority === 'High' ? 20 : 15;
        $limit = min($neededDonors, $maxDonors);

        $donors = User::where('role', 'donor')
            ->where('blood_type', $bloodRequest->blood_type)
            ->get();

        $donorsWithScores = $donors->map(function ($donor) use ($bloodRequest) {
            $matchScore = 50;
            $lastDonationDays = $donor->last_donation_date ? Carbon::parse($donor->last_donation_date)->diffInDays(now()) : null;

            if ($lastDonationDays === null || $lastDonationDays >= 90) {
                $matchScore += 30;
            } elseif ($lastDonationDays >= 60) {
                $matchScore += 15;
            } else {
                $matchScore -= 20;
            }

            $donationCount = Donation::where('donor_id', $donor->id)->count();
            if ($donationCount > 5) $matchScore += 20;
            elseif ($donationCount > 2) $matchScore += 10;
            elseif ($donationCount > 0) $matchScore += 5;

            $churn = ChurnPrediction::where('user_id', $donor->id)->latest('prediction_date')->first();
            if ($churn && $churn->likelihood_score !== null) {
                $matchScore += min(20, (float)$churn->likelihood_score * 20);
            }

            if ($bloodRequest->location && $donor->location) {
                $requestLocation = strtolower(trim($bloodRequest->location));
                $donorLocation = strtolower(trim($donor->location));
                if ($requestLocation === $donorLocation) $matchScore += 15;
                elseif (
                    strpos($donorLocation, $requestLocation) !== false ||
                    strpos($requestLocation, $donorLocation) !== false
                ) $matchScore += 5;
            }

            $matchScore = max(1, min(100, (int)$matchScore));

            return [
                'donor' => $donor,
                'match_score' => $matchScore,
                'last_donation_days' => $lastDonationDays,
                'donation_count' => $donationCount,
            ];
        })->sortByDesc('match_score')->take($limit);

        $matchedDonorsInfo = [];
        $notifiedCount = 0;

        foreach ($donorsWithScores as $donorData) {
            $donor = $donorData['donor'];
            if ($donor->role !== 'donor') continue;

            $matchScore = $donorData['match_score'];

            $existingMatch = DonorMatch::where('request_id', $bloodRequest->id)
                ->where('donor_id', $donor->id)
                ->first();
            if ($existingMatch) continue;

            DonorMatch::create([
                'request_id' => $bloodRequest->id,
                'donor_id' => $donor->id,
                'match_score' => $matchScore,
                'status' => 'Pending',
            ]);

            Notification::create([
                'user_id' => $donor->id,
                'message' => "Urgent blood request #{$bloodRequest->id}: {$bloodRequest->blood_type} blood needed ({$bloodRequest->quantity_ml} ml). Priority: {$bloodRequest->priority}. You are a potential match!",
                'type' => 'donation_request',
                'is_read' => false,
            ]);

            $matchedDonorsInfo[] = [
                'donor_id' => $donor->id,
                'name' => $donor->name,
                'distance_km' => null,
                'match_score' => $matchScore,
                'location' => $donor->location,
            ];
            $notifiedCount++;
        }

        Log::info("Request #{$bloodRequest->id}: Matched {$notifiedCount} donors using basic matching (limit={$limit}, needed={$neededDonors}ml)");

        return [
            'count' => $notifiedCount,
            'donors' => $matchedDonorsInfo,
            'method' => 'basic',
        ];
    }


    /**
     * Get matched donors for a specific blood request
     */
    public function getMatchedDonors($requestId)
    {
        $user = Auth::user();
        if ($user->role !== 'receiver') {
            return response()->json(['error' => 'forbidden'], 403);
        }

        $bloodRequest = BloodRequest::where('id', $requestId)
            ->where('receiver_id', $user->id)
            ->firstOrFail();

        $matches = DonorMatch::where('request_id', $requestId)
            ->with('donor:id,name,email,location,blood_type,role') // include role for safety
            ->orderBy('match_score', 'desc')
            ->get();

        // Filter out non-donors or deleted users
        $validMatches = $matches->filter(function ($match) {
            return $match->donor && $match->donor->role === 'donor';
        });

        $notificationSentTo = $validMatches->count() > 0 ? 'donors' : 'admin';

        return response()->json([
            'request_id' => $requestId,
            'notification_sent_to' => $notificationSentTo,
            'matched_donors_count' => $validMatches->count(),
            'matched_donors' => $validMatches->map(function ($match) {
                return [
                    'donor_id' => $match->donor_id,
                    'donor_name' => $match->donor->name,
                    'donor_email' => $match->donor->email,
                    'donor_location' => $match->donor->location,
                    'match_score' => $match->match_score,
                    'status' => $match->status,
                    'scheduled_at' => $match->scheduled_at,
                    'scheduled_location' => $match->scheduled_location,
                ];
            })->values(),
        ]);
    }
}
