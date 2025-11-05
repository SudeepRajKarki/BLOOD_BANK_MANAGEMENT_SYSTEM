<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DonorMatch;
use App\Models\Donation;
use App\Models\Campaign;
use App\Models\BloodRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Models\Notification;

class DonorMatchController extends Controller
{
    /**
     * Donor accepts a match and schedules donation.
     * Request params: either 'campaign_id' (donate at existing campaign) OR 'scheduled_at' (datetime) and 'location'.
     */
    public function accept(Request $request, $id)
    {
        $user = Auth::user();
        if (! $user || $user->role !== 'donor') {
            return response()->json(['error' => 'forbidden', 'message' => 'Only donors can accept matches'], 403);
        }

        $match = DonorMatch::find($id);
        if (! $match || $match->donor_id !== $user->id) {
            return response()->json(['error' => 'not_found'], 404);
        }

        if ($match->status !== 'Pending') {
            return response()->json(['error' => 'invalid', 'message' => 'Match already responded'], 400);
        }

        $validated = $request->validate([
            'scheduled_at' => 'nullable|date|after_or_equal:now',
            // support both 'scheduled_location' and legacy 'location'
            'scheduled_location' => 'nullable|string',
            'location' => 'nullable|string',
            'quantity_ml' => 'nullable|integer|min:1',
        ]);

        // Determine chosen schedule/location (donor-specified preferred)
        $chosenScheduledAt = $validated['scheduled_at'] ?? null;
        $chosenScheduledLocation = $validated['scheduled_location'] ?? ($validated['location'] ?? null);

        // Create donation record (match-based donations are independent of campaigns)
        $bloodRequest = BloodRequest::find($match->request_id);
        $donation = Donation::create([
            'donor_id' => $user->id,
            'blood_type' => $bloodRequest->blood_type ?? ($request->input('blood_type') ?? null),
            'quantity_ml' => $validated['quantity_ml'] ?? min(500, $bloodRequest->quantity_ml ?? 500),
            'donation_date' => $chosenScheduledAt ?? now(),
            'campaign_id' => null,
            'location' => $chosenScheduledLocation,
            'verified' => false,
        ]);

        // Update match record
    $match->status = 'Accepted';
    $match->scheduled_at = $chosenScheduledAt ?? now();
    $match->scheduled_location = $chosenScheduledLocation;
        $match->save();

        // Notify receiver (request owner)
        if ($bloodRequest) {
            Notification::create([
                'user_id' => $bloodRequest->receiver_id,
                'message' => "Donor {$user->name} has accepted to donate for request #{$bloodRequest->id}. Scheduled at {$match->scheduled_at}.",
                'type' => 'donation_confirmed',
                'is_read' => false,
            ]);

            // try {
            //     Mail::raw("Donor {$user->name} has accepted to donate for your request #{$bloodRequest->id}. Scheduled at {$match->scheduled_at}.", function ($m) use ($bloodRequest) {
            //         $receiver = $bloodRequest->receiver;
            //         if ($receiver && $receiver->email) {
            //             $m->to($receiver->email)->subject('Donation Scheduled');
            //         }
            //     });
            // } catch (\Exception $e) {
            //     // ignore
            // }
        }

        return response()->json(['message' => 'Donation scheduled', 'donation' => $donation], 200);
    }

    // Donor declines a match
    public function decline(Request $request, $id)
    {
        $user = Auth::user();
        if (! $user || $user->role !== 'donor') {
            return response()->json(['error' => 'forbidden', 'message' => 'Only donors can decline matches'], 403);
        }

        $match = DonorMatch::find($id);
        if (! $match || $match->donor_id !== $user->id) {
            return response()->json(['error' => 'not_found'], 404);
        }

        if ($match->status !== 'Pending') {
            return response()->json(['error' => 'invalid', 'message' => 'Match already responded'], 400);
        }

    $match->status = 'Declined';
        $match->save();

        // notify receiver
        $bloodRequest = BloodRequest::find($match->request_id);
        if ($bloodRequest) {
            Notification::create([
                'user_id' => $bloodRequest->receiver_id,
                'message' => "Donor {$user->name} has declined the match for request #{$bloodRequest->id}.",
                'type' => 'donation_declined',
                'is_read' => false,
            ]);
        }

        return response()->json(['message' => 'Match declined'], 200);
    }

    // List matches for current donor
    public function index(Request $request)
    {
        $user = Auth::user();
        if (! $user || $user->role !== 'donor') {
            return response()->json(['error' => 'forbidden'], 403);
        }
        $matches = DonorMatch::where('donor_id', $user->id)->get();
        return response()->json($matches);
    }
}
