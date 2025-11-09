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
     *
     * Filters donors by:
     * 1. Blood type (must match)
     * 2. Eligibility: last donation > 56 days ago (check donations table and last_donation_date)
     * 3. Uses donor_churn_prediction.py AI
     * 4. Uses donor_match.py AI
     * 5. Calculates match score in backend
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

            // Step 1: Filter donors by blood type
            $donorsQuery = User::where('role', 'donor');
            if ($bloodRequest->blood_type) {
                $donorsQuery->where('blood_type', $bloodRequest->blood_type);
            }

            $allDonors = $donorsQuery->get();
            $eligibleDonors = [];

            // Step 2: Filter by eligibility (56 days rule)
            // Check both last_donation_date field AND donations table
            $cutoffDate = Carbon::now()->subDays(56);

            foreach ($allDonors as $donor) {
                $isEligible = true;

                // Check last donation from donations table (campaign or request)
                $lastDonation = Donation::where('donor_id', $donor->id)
                    ->where('donation_date', '>=', $cutoffDate)
                    ->orderBy('donation_date', 'desc')
                    ->first();

                if ($lastDonation) {
                    // Donor has donated in last 56 days, not eligible
                    $isEligible = false;
                } else {
                    // Also check last_donation_date field
                    if ($donor->last_donation_date) {
                        $lastDonationDate = Carbon::parse($donor->last_donation_date);
                        if ($lastDonationDate->greaterThanOrEqualTo($cutoffDate)) {
                            // Last donation was less than 56 days ago
                            $isEligible = false;
                        }
                    }
                }

                if ($isEligible) {
                    $eligibleDonors[] = $donor;
                }
            }

            if (empty($eligibleDonors)) {
                Log::warning("Request #{$bloodRequest->id}: No eligible donors found (56-day rule)");
                return [
                    'count' => 0,
                    'donors' => [],
                    'method' => 'ai',
                ];
            }

            // Step 3: Prepare donor data for AI
            $donorsData = [];
            foreach ($eligibleDonors as $donor) {
                // Calculate last donation days (from donations table or last_donation_date)
                $lastDonation = Donation::where('donor_id', $donor->id)
                    ->orderBy('donation_date', 'desc')
                    ->first();

                $lastDonationDays = null;
                if ($lastDonation) {
                    $lastDonationDays = Carbon::parse($lastDonation->donation_date)->diffInDays(now());
                } elseif ($donor->last_donation_date) {
                    $lastDonationDays = Carbon::parse($donor->last_donation_date)->diffInDays(now());
                } else {
                    // No donation history, assume very long time (eligible)
                    $lastDonationDays = 365;
                }

                $donationCount = Donation::where('donor_id', $donor->id)->count();

                // Get response rate from churn prediction (if available)
                $churn = ChurnPrediction::where('user_id', $donor->id)
                    ->latest('prediction_date')
                    ->first();

                // Calculate response rate based on donation history
                // For now, use a simple heuristic: more donations = higher response rate
                $responseRate = 0.5; // default
                if ($donationCount > 5) {
                    $responseRate = 0.9;
                } elseif ($donationCount > 2) {
                    $responseRate = 0.7;
                } elseif ($donationCount > 0) {
                    $responseRate = 0.6;
                }

                $donorsData[] = [
                    'id' => $donor->id,
                    'name' => $donor->name,
                    'email' => $donor->email,
                    'location' => $donor->location,
                    'city' => $donor->location,
                    'blood_group' => $donor->blood_type,
                    'blood_type' => $donor->blood_type,
                    'last_donation_date' => $lastDonation ? $lastDonation->donation_date : $donor->last_donation_date,
                    'last_donation_days' => $lastDonationDays,
                    'last_donation' => $lastDonationDays,
                    'response_rate' => $responseRate,
                    'total_donations' => $donationCount,
                    'donation_count' => $donationCount,
                ];
            }

            // Step 4: Use donor_churn_prediction.py AI
            $churnResults = [];
            try {
                $churnResponse = Http::timeout($timeout)->post($aiUrl . '/churn-predict', [
                    'candidates' => $donorsData,
                ]);

                if ($churnResponse->successful()) {
                    $churnResults = $churnResponse->json();
                    // If response is a list of results
                    if (is_array($churnResults) && isset($churnResults[0]['donor_id'])) {
                        // Already in correct format
                    } else {
                        // Convert to indexed array by donor_id
                        $churnMap = [];
                        foreach ($churnResults as $result) {
                            if (isset($result['donor_id'])) {
                                $churnMap[$result['donor_id']] = $result;
                            }
                        }
                        $churnResults = $churnMap;
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Request #{$bloodRequest->id}: Churn prediction AI failed: " . $e->getMessage());
            }

            // Step 5: Use donor_match.py AI for distance-based matching
            $matchedDonors = [];
            try {
                $matchResponse = Http::timeout($timeout)->post($aiUrl . '/match-donor', [
                    'city' => $bloodRequest->location,
                    'location' => $bloodRequest->location,
                    'blood_type' => $bloodRequest->blood_type,
                    'k' => $k * 2, // Get more candidates for scoring
                    'donors' => $donorsData,
                ]);

                if ($matchResponse->successful()) {
                    $matchData = $matchResponse->json();
                    $matchedDonors = $matchData['nearest_donors'] ?? [];
                }
            } catch (\Exception $e) {
                Log::warning("Request #{$bloodRequest->id}: Donor match AI failed: " . $e->getMessage());
                // Fallback: create matched donors from eligible donors
                foreach ($donorsData as $donorData) {
                    $matchedDonors[] = [
                        'id' => $donorData['id'],
                        'donor_id' => $donorData['id'],
                        'name' => $donorData['name'],
                        'distance_km' => 0, // Unknown distance
                        'blood_group' => $donorData['blood_type'],
                    ];
                }
            }

            // Step 6: Calculate match score for each donor and rank them
            $donorsWithScores = [];
            foreach ($matchedDonors as $match) {
                $donorId = $match['id'] ?? $match['donor_id'] ?? null;
                if (!$donorId) continue;

                $donor = collect($donorsData)->firstWhere('id', $donorId);
                if (!$donor) continue;

                // Initialize match score
                $matchScore = 50; // Base score

                // Factor 1: Churn probability (lower churn = higher score)
                $churnData = null;
                if (is_array($churnResults)) {
                    if (isset($churnResults[0]) && is_array($churnResults[0])) {
                        // List format
                        $churnData = collect($churnResults)->firstWhere('donor_id', $donorId);
                    } else {
                        // Map format
                        $churnData = $churnResults[$donorId] ?? null;
                    }
                }

                if ($churnData) {
                    $churnProb = $churnData['churn_probability'] ?? $churnData['score'] ?? 0.5;
                    // Lower churn probability = higher score (inverse)
                    $matchScore += (1 - $churnProb) * 30; // Max 30 points
                } else {
                    // Fallback: use response rate
                    $responseRate = $donor['response_rate'] ?? 0.5;
                    $matchScore += $responseRate * 30;
                }

                // Factor 2: Distance (closer = higher score)
                $distanceKm = $match['distance_km'] ?? 999;
                if ($distanceKm > 0 && $distanceKm < 999) {
                    // Closer donors get higher scores
                    $distanceScore = max(0, 40 - ($distanceKm * 2)); // Max 40 points, decreases with distance
                    $matchScore += $distanceScore;
                } else {
                    // Unknown distance, give moderate score
                    $matchScore += 20;
                }

                // Factor 3: Donation history (more donations = higher score)
                $donationCount = $donor['total_donations'] ?? $donor['donation_count'] ?? 0;
                if ($donationCount > 5) {
                    $matchScore += 15;
                } elseif ($donationCount > 2) {
                    $matchScore += 10;
                } elseif ($donationCount > 0) {
                    $matchScore += 5;
                }

                // Factor 4: Last donation days (longer gap = slightly higher score, but not too long)
                $lastDonationDays = $donor['last_donation_days'] ?? $donor['last_donation'] ?? 90;
                if ($lastDonationDays >= 56 && $lastDonationDays <= 180) {
                    // Optimal range: eligible but not too long
                    $matchScore += 10;
                } elseif ($lastDonationDays > 180) {
                    // Very long gap, might be less engaged
                    $matchScore += 5;
                }

                // Normalize score to 1-100 range
                $matchScore = max(1, min(100, round($matchScore)));

                $donorsWithScores[] = [
                    'donor_id' => $donorId,
                    'donor' => $donor,
                    'match' => $match,
                    'match_score' => $matchScore,
                    'churn_data' => $churnData,
                    'distance_km' => $distanceKm,
                ];
            }

            // Sort by match score (descending) and take top k
            usort($donorsWithScores, function ($a, $b) {
                return $b['match_score'] - $a['match_score'];
            });
            $topDonors = array_slice($donorsWithScores, 0, $k);

            // Step 7: Create DonorMatch records and send notifications
            $matchedDonorsInfo = [];
            $notifiedCount = 0;

            foreach ($topDonors as $donorData) {
                $donorId = $donorData['donor_id'];

                $donor = User::find($donorId);
                if (!$donor || $donor->role !== 'donor') continue;

                // Check if match already exists
                $existingMatch = DonorMatch::where('request_id', $bloodRequest->id)
                    ->where('donor_id', $donorId)
                    ->first();
                if ($existingMatch) continue;

                // Create DonorMatch record
                DonorMatch::create([
                    'request_id' => $bloodRequest->id,
                    'donor_id' => $donorId,
                    'match_score' => $donorData['match_score'],
                    'status' => 'Pending',
                ]);

                // Send notification to donor
                Notification::create([
                    'user_id' => $donorId,
                    'message' => "Urgent blood request #{$bloodRequest->id}: {$bloodRequest->blood_type} blood needed ({$bloodRequest->quantity_ml} ml). Priority: {$bloodRequest->priority}. You are a potential match!",
                    'type' => 'donation_request',
                    'is_read' => false,
                ]);

                $matchedDonorsInfo[] = [
                    'donor_id' => $donorId,
                    'name' => $donor->name ?? 'Unknown',
                    'distance_km' => round($donorData['distance_km'], 2),
                    'match_score' => $donorData['match_score'],
                    'location' => $donor->location ?? null,
                    'churn_probability' => $donorData['churn_data']['churn_probability'] ?? null,
                ];
                $notifiedCount++;
            }

            $eligibleCount = count($eligibleDonors);
            Log::info("Request #{$bloodRequest->id}: Matched {$notifiedCount} donors using AI matching (k={$k}, eligible={$eligibleCount}, needed={$neededDonors}ml)");

            return [
                'count' => $notifiedCount,
                'donors' => $matchedDonorsInfo,
                'method' => 'ai',
            ];
        } catch (\Exception $e) {
            Log::warning("Request #{$bloodRequest->id}: AI donor matching failed: " . $e->getMessage());
            return $this->matchDonorsBasic($bloodRequest);
        }
    }


    /**
     * Fallback: Basic donor matching without AI (matches by blood type)
     * Returns array with 'count' and 'donors' information
     * Calculates match scores based on donor history and eligibility
     * Also applies 56-day eligibility rule
     */
    private function matchDonorsBasic(BloodRequest $bloodRequest)
    {
        $averageDonationMl = 450;
        $neededDonors = max(1, ceil($bloodRequest->quantity_ml / $averageDonationMl));
        $maxDonors = $bloodRequest->priority === 'High' ? 20 : 15;
        $limit = min($neededDonors, $maxDonors);

        // Filter by blood type
        $donorsQuery = User::where('role', 'donor')
            ->where('blood_type', $bloodRequest->blood_type);

        $allDonors = $donorsQuery->get();
        $eligibleDonors = [];

        // Filter by eligibility (56 days rule)
        $cutoffDate = Carbon::now()->subDays(56);

        foreach ($allDonors as $donor) {
            $isEligible = true;

            // Check last donation from donations table (campaign or request)
            $lastDonation = Donation::where('donor_id', $donor->id)
                ->where('donation_date', '>=', $cutoffDate)
                ->orderBy('donation_date', 'desc')
                ->first();

            if ($lastDonation) {
                // Donor has donated in last 56 days, not eligible
                $isEligible = false;
            } else {
                // Also check last_donation_date field
                if ($donor->last_donation_date) {
                    $lastDonationDate = Carbon::parse($donor->last_donation_date);
                    if ($lastDonationDate->greaterThanOrEqualTo($cutoffDate)) {
                        // Last donation was less than 56 days ago
                        $isEligible = false;
                    }
                }
            }

            if ($isEligible) {
                $eligibleDonors[] = $donor;
            }
        }

        if (empty($eligibleDonors)) {
            Log::warning("Request #{$bloodRequest->id}: No eligible donors found in basic matching (56-day rule)");
            return [
                'count' => 0,
                'donors' => [],
                'method' => 'basic',
            ];
        }

        $donorsWithScores = collect($eligibleDonors)->map(function ($donor) use ($bloodRequest) {
            $matchScore = 50;

            // Calculate last donation days
            $lastDonation = Donation::where('donor_id', $donor->id)
                ->orderBy('donation_date', 'desc')
                ->first();

            $lastDonationDays = null;
            if ($lastDonation) {
                $lastDonationDays = Carbon::parse($lastDonation->donation_date)->diffInDays(now());
            } elseif ($donor->last_donation_date) {
                $lastDonationDays = Carbon::parse($donor->last_donation_date)->diffInDays(now());
            } else {
                $lastDonationDays = 365; // No donation history
            }

            // Score based on last donation (eligible range is 56-180 days optimal)
            if ($lastDonationDays >= 56 && $lastDonationDays <= 180) {
                $matchScore += 30;
            } elseif ($lastDonationDays > 180) {
                $matchScore += 15;
            }

            $donationCount = Donation::where('donor_id', $donor->id)->count();
            if ($donationCount > 5) $matchScore += 20;
            elseif ($donationCount > 2) $matchScore += 10;
            elseif ($donationCount > 0) $matchScore += 5;

            $churn = ChurnPrediction::where('user_id', $donor->id)->latest('prediction_date')->first();
            if ($churn && $churn->likelihood_score !== null) {
                // Lower churn = higher score
                $churnScore = (1 - (float)$churn->likelihood_score) * 20;
                $matchScore += $churnScore;
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

        $eligibleCount = count($eligibleDonors);
        Log::info("Request #{$bloodRequest->id}: Matched {$notifiedCount} donors using basic matching (limit={$limit}, eligible={$eligibleCount}, needed={$neededDonors}ml)");

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
