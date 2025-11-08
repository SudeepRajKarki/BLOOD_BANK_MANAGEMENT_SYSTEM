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
            // Calculate number of donors needed based on quantity
            // Assuming average donation is 450-500ml, we calculate k (number of donors to match)
            $averageDonationMl = 450;
            $neededDonors = max(1, ceil($bloodRequest->quantity_ml / $averageDonationMl));
            // Set a reasonable maximum (e.g., 10-15 donors) and minimum based on priority
            $maxDonors = $bloodRequest->priority === 'High' ? 15 : 10;
            $minDonors = $bloodRequest->priority === 'High' ? 3 : 2;
            $k = max($minDonors, min($neededDonors, $maxDonors));

            // Gather donors from DB and include as payload to AI (similar to AiMatchingController)
            $donorsQuery = User::where('role', 'donor');
            if ($bloodRequest->blood_type) {
                $donorsQuery->where('blood_type', $bloodRequest->blood_type);
            }

            $donors = $donorsQuery->get()->map(function ($d) {
                // Enrich donor payload for AI: include last_donation_days, response_rate, and total donations
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
                    'blood_group' => $d->blood_type, // AI service expects 'blood_group' key
                    'last_donation_date' => $d->last_donation_date,
                    'last_donation_days' => $lastDonationDays,
                    'response_rate' => $responseRate,
                    'total_donations' => $donationCount,
                ];
            })->toArray();

            // Use AI matching service to find best donor matches
            $response = Http::timeout($timeout)->post($aiUrl . '/match-donor', [
                'blood_type' => $bloodRequest->blood_type,
                'location' => $bloodRequest->location,
                'quantity_ml' => $bloodRequest->quantity_ml,
                'priority' => $bloodRequest->priority,
                'k' => $k, // Pass calculated number of donors needed
                'donors' => $donors, // Pass donors to AI service
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                // AI service returns 'nearest_donors' array with donor info
                $matchedDonors = $responseData['nearest_donors'] ?? [];
                $aiError = $responseData['error'] ?? null;

                // If AI returned an error (e.g., invalid location), log it and use basic matching
                if ($aiError) {
                    Log::warning("Request #{$bloodRequest->id}: AI matching error: {$aiError}. Using basic matching.");
                    return $this->matchDonorsBasic($bloodRequest);
                }

                // Create donor matches and send notifications for AI-matched donors
                if (!empty($matchedDonors)) {
                    $matchedDonorsInfo = [];
                    $notifiedCount = 0;

                    foreach ($matchedDonors as $match) {
                        $donorId = $match['id'] ?? $match['donor_id'] ?? null;
                        // Use distance as match score (inverse - closer is better, so use 1/distance or similar)
                        $distanceKm = $match['distance_km'] ?? 0;
                        $matchScore = $distanceKm > 0 ? max(1, round(100 / ($distanceKm + 1))) : 1;

                        if ($donorId) {
                            // Check if match already exists
                            $existingMatch = DonorMatch::where('request_id', $bloodRequest->id)
                                ->where('donor_id', $donorId)
                                ->first();

                            if (!$existingMatch) {
                                $donor = User::find($donorId);

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
                        }
                    }

                    Log::info("Request #{$bloodRequest->id}: Matched {$notifiedCount} donors using AI matching (k={$k}, needed={$neededDonors}ml)");

                    return [
                        'count' => $notifiedCount,
                        'donors' => $matchedDonorsInfo,
                        'method' => 'ai',
                    ];
                } else {
                    // If AI returned no matches, fallback to basic matching
                    Log::info("Request #{$bloodRequest->id}: AI returned no donor matches, using basic matching");
                    return $this->matchDonorsBasic($bloodRequest);
                }
            } else {
                // AI service failed, fallback to basic matching
                Log::warning("Request #{$bloodRequest->id}: AI donor matching failed, using basic matching");
                return $this->matchDonorsBasic($bloodRequest);
            }
        } catch (\Exception $e) {
            // AI service unavailable, fallback to basic matching
            Log::warning("Request #{$bloodRequest->id}: AI donor matching service unavailable: " . $e->getMessage());
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
        // Calculate number of donors needed based on quantity
        $averageDonationMl = 450;
        $neededDonors = max(1, ceil($bloodRequest->quantity_ml / $averageDonationMl));
        // Set a reasonable maximum for basic matching (limit to prevent spamming all donors)
        $maxDonors = $bloodRequest->priority === 'High' ? 20 : 15;
        $limit = min($neededDonors, $maxDonors);

        // Get compatible donors based on blood type with donation history
        $donors = User::where('role', 'donor')
            ->where('blood_type', $bloodRequest->blood_type)
            ->get();

        // Calculate match scores for each donor
        $donorsWithScores = $donors->map(function ($donor) use ($bloodRequest) {
            $matchScore = 50; // Base score

            // Calculate days since last donation
            $lastDonationDays = null;
            if ($donor->last_donation_date) {
                try {
                    $lastDonationDays = Carbon::parse($donor->last_donation_date)->diffInDays(now());
                } catch (\Exception $e) {
                    $lastDonationDays = null;
                }
            }

            // Boost score if eligible to donate (90+ days since last donation, or never donated)
            if ($lastDonationDays === null || $lastDonationDays >= 90) {
                $matchScore += 30; // Eligible to donate
            } elseif ($lastDonationDays >= 60) {
                $matchScore += 15; // Almost eligible
            } else {
                $matchScore -= 20; // Not eligible yet
            }

            // Boost score based on donation history
            $donationCount = Donation::where('donor_id', $donor->id)->count();
            if ($donationCount > 5) {
                $matchScore += 20; // Experienced donor
            } elseif ($donationCount > 2) {
                $matchScore += 10; // Regular donor
            } elseif ($donationCount > 0) {
                $matchScore += 5; // Has donated before
            }

            // Boost score based on response rate (churn prediction)
            $churn = ChurnPrediction::where('user_id', $donor->id)->latest('prediction_date')->first();
            if ($churn && $churn->likelihood_score !== null) {
                // Higher likelihood_score means more likely to respond
                $responseBonus = min(20, (float)$churn->likelihood_score * 20);
                $matchScore += $responseBonus;
            }

            // Location matching (simple string matching for basic matching)
            if ($bloodRequest->location && $donor->location) {
                $requestLocation = strtolower(trim($bloodRequest->location));
                $donorLocation = strtolower(trim($donor->location));
                if ($requestLocation === $donorLocation) {
                    $matchScore += 15; // Same location
                } elseif (strpos($donorLocation, $requestLocation) !== false ||
                          strpos($requestLocation, $donorLocation) !== false) {
                    $matchScore += 5; // Similar location
                }
            }

            // Ensure score is between 1 and 100
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
            $matchScore = $donorData['match_score'];

            // Check if match already exists
            $existingMatch = DonorMatch::where('request_id', $bloodRequest->id)
                ->where('donor_id', $donor->id)
                ->first();

            if (!$existingMatch) {
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
                    'distance_km' => null, // Not calculated in basic matching
                    'match_score' => $matchScore,
                    'location' => $donor->location,
                ];
                $notifiedCount++;
            }
        }

        Log::info("Request #{$bloodRequest->id}: Matched {$notifiedCount} donors using basic matching with calculated scores (limit={$limit}, needed={$neededDonors}ml)");

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
            ->with('donor:id,name,email,location,blood_type')
            ->orderBy('match_score', 'desc')
            ->get();

        // Determine if request was sent to admin or donors
        $hasDonorMatches = $matches->count() > 0;
        $notificationSentTo = $hasDonorMatches ? 'donors' : 'admin';

        return response()->json([
            'request_id' => $requestId,
            'notification_sent_to' => $notificationSentTo,
            'matched_donors_count' => $matches->count(),
            'matched_donors' => $matches->map(function ($match) {
                return [
                    'donor_id' => $match->donor_id,
                    'donor_name' => $match->donor->name ?? 'Unknown',
                    'donor_email' => $match->donor->email ?? null,
                    'donor_location' => $match->donor->location ?? null,
                    'match_score' => $match->match_score,
                    'status' => $match->status,
                    'scheduled_at' => $match->scheduled_at,
                    'scheduled_location' => $match->scheduled_location,
                ];
            }),
        ]);
    }
}
