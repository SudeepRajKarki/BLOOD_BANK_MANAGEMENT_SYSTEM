<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BloodRequest;
use App\Models\BloodInventory;
use App\Models\User;
use App\Models\DonorMatch;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

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
                    ->orderBy('created_at', 'desc')
                    ->get();

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
    // 1. Predict priority using AI/NLP
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
        // fallback to Medium
    }

    // ----------------------
    // 2. Check inventory availability
    // ----------------------
    $inventoryQuery = BloodInventory::where('blood_type', $bloodType);
    if ($location) $inventoryQuery->where('location', $location);
    $inventoryTotal = (int) $inventoryQuery->sum('quantity_ml');

    // always pending for admin or donor approval
    $requestStatus = 'Pending';

    // ----------------------
    // 3. Create Blood Request
    // ----------------------
    $bloodRequest = BloodRequest::create([
        'receiver_id' => $user->id,
        'blood_type' => $bloodType,
        'quantity_ml' => $quantity,
        'reason' => $validated['reason'],
        'status' => $requestStatus,
        'location' => $location,
        'priority' => $priority,
    ]);

    // ----------------------
    // 4. Handle inventory vs donor matching
    // ----------------------
    if ($inventoryTotal >= $quantity) {
        // Notify admins to approve inventory request
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'message' => "New blood request #{$bloodRequest->id} requires inventory approval",
                'type' => 'request_approval',
                'is_read' => false,
            ]);
        }
    } else {
        // Trigger donor AI matching
        $this->matchDonors($bloodRequest);
    }

    return response()->json([
        'request' => $bloodRequest,
        'inventory_available' => $inventoryTotal >= $quantity,
        'priority' => $priority,
    ], 201);
}


   private function matchDonors(BloodRequest $bloodRequest)
{
    $donors = User::where('role', 'donor')
                  ->where('blood_group', $bloodRequest->blood_type)
                  ->get();

    foreach ($donors as $donor) {
        // optionally filter by distance if you have lat/lng fields
        DonorMatch::create([
            'request_id' => $bloodRequest->id,
            'donor_id' => $donor->id,
            'match_score' => 1, // can use AI for scoring
        ]);

        Notification::create([
            'user_id' => $donor->id,
            'message' => "You are a potential match for blood request #{$bloodRequest->id} ({$bloodRequest->blood_type}, {$bloodRequest->quantity_ml} ml).",
            'type' => 'donation_request',
            'is_read' => false,
        ]);
    }
    }

}

