<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BloodRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\BloodInventory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Models\Notification;
use App\Models\User;
use App\Models\DonorMatch;
use Illuminate\Support\Facades\DB;

class RequestController extends Controller
{
    public function index()
    {
        $requests = BloodRequest::with('receiver')->get();
        return response()->json($requests);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'blood_type' => 'required|string|max:3',
            'quantity_ml' => 'required|integer|min:1',
            'reason' => 'required|string',
            'location' => 'nullable|string',
        ]);

        $user = Auth::user();
        if (! $user || $user->role !== 'receiver') {
            return response()->json(['error' => 'forbidden', 'message' => 'Only receivers can create blood requests'], 403);
        }

        // Check inventory for availability (filter by blood type and optional location)
        // Consider the total available quantity across inventory rows (or at specific location)
        $baseInvQuery = BloodInventory::where('blood_type', $validated['blood_type']);
        $totalAvailable = (int) $baseInvQuery->sum('quantity_ml');

        $locationAvailable = null;
        if (! empty($validated['location'])) {
            $locationAvailable = (int) BloodInventory::where('blood_type', $validated['blood_type'])
                ->where('location', $validated['location'])
                ->sum('quantity_ml');
        }

        // find a single inventory row that can fulfill the request (prefer location if provided)
        $inventory = BloodInventory::where('blood_type', $validated['blood_type'])
            ->when(! empty($validated['location']), function ($q) use ($validated) {
                return $q->where('location', $validated['location']);
            })
            ->where('quantity_ml', '>=', $validated['quantity_ml'])
            ->first();

        $aiUrl = config('ai.url', 'http://127.0.0.1:5000');
        $timeout = config('ai.timeout', 5);

        // Predict priority from reason (NLP) in both cases
        try {
            $priorityResp = Http::timeout($timeout)->post($aiUrl . '/predict-priority', ['reason' => $validated['reason']]);
            $priority = $priorityResp->successful() ? ($priorityResp->json('priority') ?? 'Medium') : 'Medium';
        } catch (\Exception $e) {
            $priority = 'Medium';
        }

        // If available in inventory we still create a request but mark as Pending for admin approval.
        // Consider a request fulfilled by inventory when any of the following is true:
        // - a single inventory row can satisfy the requested quantity (already in $inventory)
        // - the requested location has enough total quantity (locationAvailable)
        // - the total available across all locations is enough (totalAvailable)
        $availableByLocation = (! is_null($locationAvailable) && $locationAvailable >= $validated['quantity_ml']);
        $availableByTotal = $totalAvailable >= $validated['quantity_ml'];

        if (($inventory && $inventory->quantity_ml >= $validated['quantity_ml']) || $availableByLocation || $availableByTotal) {
            $bloodRequest = BloodRequest::create([
                'receiver_id' => $user->id,
                'blood_type' => $validated['blood_type'],
                'quantity_ml' => $validated['quantity_ml'],
                'reason' => $validated['reason'],
                'priority' => $priority,
                'status' => 'Pending', // admin must approve/deny
                'location' => $validated['location'] ?? ($inventory->location ?? null),
            ]);

            // Notify admins to review the request
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'message' => "New blood request #{$bloodRequest->id} requires approval",
                    'type' => 'request_approval',
                    'is_read' => false,
                ]);

                // try {
                //     Mail::raw("A new blood request (ID: {$bloodRequest->id}) for {$bloodRequest->blood_type} ({$bloodRequest->quantity_ml} ml) requires your approval.", function ($m) use ($admin) {
                //         $m->to($admin->email)->subject('Blood Request Approval Needed');
                //     });
                // } catch (\Exception $e) {
                //     // swallow mail errors but continue
                // }
            }

            return response()->json(['message' => 'Request created and awaiting admin approval', 'request' => $bloodRequest], 201);
        }

        // Not available: request donors and use AI
        // Create a pending blood request
        $bloodRequest = BloodRequest::create([
            'receiver_id' => $user->id,
            'blood_type' => $validated['blood_type'],
            'quantity_ml' => $validated['quantity_ml'],
            'reason' => $validated['reason'],
            'priority' => $priority,
            'status' => 'Pending',
            'location' => $validated['location'] ?? null,
        ]);

        // Ask AI/ML service to find donors (best-effort)
        try {
            // gather donors from DB and enrich payload so AI receives real donor records
            $donorsQuery = User::where('role', 'donor');
            if (! empty($validated['blood_type'])) {
                $donorsQuery->where('blood_group', $validated['blood_type']);
            }
            $donors = $donorsQuery->get()->map(function ($d) {
                return [
                    'id' => $d->id,
                    'name' => $d->name,
                    'email' => $d->email,
                    'location' => $d->location,
                    'blood_group' => $d->blood_group,
                    'last_donation_date' => $d->last_donation_date,
                ];
            })->toArray();

            $payload = [
                'blood_type' => $validated['blood_type'],
                'city' => $validated['location'] ?? ($inventory->location ?? null),
                'location' => $validated['location'] ?? ($inventory->location ?? null),
                'quantity_ml' => $validated['quantity_ml'],
                'request_id' => $bloodRequest->id,
                'donors' => $donors,
            ];

            $matchResp = Http::timeout($timeout)->post($aiUrl . '/match-donor', $payload);

            $matchData = $matchResp->successful() ? $matchResp->json() : [];

            // normalize to a donor list the churn predictor expects
            $donorList = [];
            if (is_array($matchData) && isset($matchData['nearest_donors']) && is_array($matchData['nearest_donors'])) {
                $donorList = $matchData['nearest_donors'];
            } elseif (is_array($matchData)) {
                // assume matchData is already a list of donors
                $donorList = array_values($matchData);
            }

            $matches = $donorList;
        } catch (\Exception $e) {
            $matches = [];
        }

        $topMatches = [];
        if (! empty($matches) && is_array($matches)) {
            // Ask churn predictor to score candidates (optional)
            try {
                // Build candidate payload using DB info so churn model has required fields
                $candidates = [];
                foreach ($matches as $mitem) {
                    $donorId = is_array($mitem) ? ($mitem['id'] ?? ($mitem['donor_id'] ?? null)) : $mitem;
                    if (! $donorId) continue;
                    $donorUser = User::find($donorId);
                    if (! $donorUser) continue;

                    $lastDonationDays = null;
                    if (! empty($donorUser->last_donation_date)) {
                        try {
                            $lastDonationDays = \Carbon\Carbon::parse($donorUser->last_donation_date)->diffInDays(now());
                        } catch (\Exception $e) {
                            $lastDonationDays = null;
                        }
                    }

                    $churnRecord = \App\Models\ChurnPrediction::where('user_id', $donorUser->id)->latest('prediction_date')->first();
                    $responseRate = $churnRecord ? ($churnRecord->likelihood_score ?? null) : null;

                    $donationCount = \App\Models\Donation::where('donor_id', $donorUser->id)->count();

                    $candidates[] = [
                        'id' => $donorUser->id,
                        'last_donation_days' => $lastDonationDays,
                        'response_rate' => $responseRate,
                        'total_donations' => $donationCount,
                    ];
                }

                $churnResp = Http::timeout($timeout)->post($aiUrl . '/churn-predict', ['candidates' => $candidates]);
                $churnScores = $churnResp->successful() ? $churnResp->json() : [];
            } catch (\Exception $e) {
                $churnScores = [];
            }

            // Merge scores into matches and sort. Expect churnScores to be map of donor_id => score or array of {donor_id, score}
            // normalize churnScores into map donor_id => score
            $scoreMap = [];
            if (is_array($churnScores)) {
                foreach ($churnScores as $cs) {
                    if (is_array($cs)) {
                        $did = $cs['donor_id'] ?? ($cs['id'] ?? null);
                        $s = $cs['score'] ?? ($cs['churn_probability'] ?? null);
                        if ($did) $scoreMap[$did] = $s;
                    }
                }
            }

            foreach ($matches as $m) {
                $donorId = is_array($m) ? ($m['id'] ?? ($m['donor_id'] ?? null)) : $m;
                $score = $scoreMap[$donorId] ?? null;
                if (is_array($m)) {
                    $topMatches[] = array_merge($m, ['churn_score' => $score]);
                } else {
                    $topMatches[] = ['value' => $m, 'churn_score' => $score];
                }
            }

            usort($topMatches, function ($a, $b) {
                $sa = $a['churn_score'] ?? 0;
                $sb = $b['churn_score'] ?? 0;
                // higher score first
                return $sb <=> $sa;
            });

            // Persist top N matches and notify them, but only if donor is eligible and churn_score indicates likelihood
            $notifyCount = min(5, count($topMatches));
            for ($i = 0; $i < $notifyCount; $i++) {
                $candidate = $topMatches[$i];
                $donorId = null;
                if (is_array($candidate)) {
                    $donorId = $candidate['id'] ?? ($candidate['donor_id'] ?? ($candidate['value'] ?? null));
                } else {
                    $donorId = $candidate ?? null;
                }
                if (! $donorId) continue;

                // persist donor match
                try {
                    DonorMatch::create([
                        'request_id' => $bloodRequest->id,
                        'donor_id' => $donorId,
                        'match_score' => $candidate['churn_score'] ?? 0,
                    ]);
                } catch (\Exception $e) {
                    // ignore persistence errors for now
                }

                // create notification record and attempt email only if donor eligible and churn score ok
                $donor = User::find($donorId);
                if ($donor) {
                    // check donor eligibility: verified, health_status not indicating disqualification, and last donation > 90 days
                    $eligible = true;
                    // In local/dev environment allow notifications for testing even if donor is not verified
                    if (! app()->environment('local') && ! $donor->is_verified) $eligible = false;
                    if (! empty($donor->health_status)) {
                        $hs = strtolower($donor->health_status);
                        if (str_contains($hs, 'not') || str_contains($hs, 'un') || str_contains($hs, 'disqual') || str_contains($hs, 'unfit')) {
                            $eligible = false;
                        }
                    }
                    if (! empty($donor->last_donation_date)) {
                        try {
                            $ld = \Carbon\Carbon::parse($donor->last_donation_date);
                            if ($ld->diffInDays(now()) < 90) $eligible = false;
                        } catch (\Exception $e) {
                            // ignore parse errors
                        }
                    }

                    // check churn_score threshold (if present)
                    $churnScore = $candidate['churn_score'] ?? null;
                    $churnOk = true;
                    if (! is_null($churnScore)) {
                        $churnOk = ($churnScore >= 0.5); // threshold; tune as needed
                    }

                    if ($eligible && $churnOk) {
                        Notification::create([
                            'user_id' => $donor->id,
                            'message' => "You are a potential match for blood request #{$bloodRequest->id} (Type: {$bloodRequest->blood_type}, Qty: {$bloodRequest->quantity_ml} ml). Please respond if you can donate.",
                            'type' => 'donation_request',
                            'is_read' => false,
                        ]);

                        // try {
                        //     Mail::raw("You are a potential match for blood request #{$bloodRequest->id} (Type: {$bloodRequest->blood_type}, Qty: {$bloodRequest->quantity_ml} ml). Please login to the app to respond.", function ($m) use ($donor) {
                        //         $m->to($donor->email)->subject('Blood Donation Request');
                        //     });
                        // } catch (\Exception $e) {
                        //     // ignore mail failures
                        // }
                    }
                }
            }
        }

        return response()->json(['request' => $bloodRequest, 'matches' => $topMatches], 201);
    }
}
