<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DonorMatch;
use App\Models\Donation;
use App\Models\BloodRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\Notification;

class DonorMatchController extends Controller
{
    /**
     * Donor accepts a match and schedules donation
     */
    public function accept(Request $request, int $id)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'donor') {
            return response()->json(['error' => 'forbidden', 'message' => 'Only donors can accept matches'], 403);
        }

        $match = DonorMatch::find($id);
        if (!$match || $match->donor_id !== $user->id) {
            return response()->json(['error' => 'not_found'], 404);
        }

        if ($match->status !== 'Pending') {
            return response()->json(['error' => 'invalid', 'message' => 'Match already responded'], 400);
        }

        $validated = $request->validate([
            'scheduled_at' => 'nullable|date|after_or_equal:now',
            'scheduled_location' => 'nullable|string',
            'location' => 'nullable|string',
            'quantity_ml' => 'nullable|integer|min:1',
        ]);

        $scheduledAt = $validated['scheduled_at'] ?? now();
        $scheduledLocation = $validated['scheduled_location'] ?? ($validated['location'] ?? null);

        // Get the associated blood request
        $bloodRequest = BloodRequest::find($match->request_id);

        // Create donation record
        $donation = Donation::create([
            'donor_id' => $user->id,
            'blood_type' => $bloodRequest->blood_type ?? null,
            'quantity_ml' => $validated['quantity_ml'] ?? min(500, $bloodRequest->quantity_ml ?? 500),
            'donation_date' => $scheduledAt,
            'campaign_id' => null,
            'request_id' => $bloodRequest->id ?? null, // ✅ Save request ID
            'location' => $scheduledLocation,
            'verified' => false,
        ]);

        // Update match
        $match->status = 'Accepted';
        $match->scheduled_at = $scheduledAt;
        $match->scheduled_location = $scheduledLocation;
        $match->save();

        // Notify receiver
        if ($bloodRequest) {
            Notification::create([
                'user_id' => $bloodRequest->receiver_id,
                'message' => "Donor {$user->name} accepted donation for request #{$bloodRequest->id}, scheduled at {$scheduledAt}.",
                'type' => 'donation_confirmed',
                'is_read' => false,
            ]);
        }

        return response()->json([
            'message' => 'Donation scheduled',
            'donation' => $donation->load('request.receiver') // eager load receiver for frontend
        ], 200);
    }

    /**
     * Donor declines a match
     */
    public function decline(int $id)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'donor') {
            return response()->json(['error' => 'forbidden'], 403);
        }

        $match = DonorMatch::find($id);
        if (!$match || $match->donor_id !== $user->id) {
            return response()->json(['error' => 'not_found'], 404);
        }

        if ($match->status !== 'Pending') {
            return response()->json(['error' => 'invalid', 'message' => 'Match already responded'], 400);
        }

        $match->status = 'Declined';
        $match->save();

        // Notify receiver
        $bloodRequest = BloodRequest::find($match->request_id);
        if ($bloodRequest) {
            Notification::create([
                'user_id' => $bloodRequest->receiver_id,
                'message' => "Donor {$user->name} declined the match for request #{$bloodRequest->id}.",
                'type' => 'donation_declined',
                'is_read' => false,
            ]);
        }

        return response()->json(['message' => 'Match declined'], 200);
    }

    /**
     * List matches for current donor
     */
    public function index()
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'donor') {
            return response()->json(['error' => 'forbidden'], 403);
        }

        $matches = DonorMatch::with(['request.receiver'])
            ->where('donor_id', $user->id)
            ->get();

        return response()->json($matches);
    }
}
