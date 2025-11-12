<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DonorMatch;
use App\Models\Donation;
use App\Models\BloodRequest;
use App\Models\BloodInventory;
use Illuminate\Support\Facades\Auth;
use App\Models\Notification;
use Carbon\Carbon;

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

        // Check eligibility: last donation must be > 56 days ago
        $cutoffDate = Carbon::now()->subDays(56);

        // Check donations table (campaign or request donations)
        $recentDonation = Donation::where('donor_id', $user->id)
            ->where('donation_date', '>=', $cutoffDate)
            ->orderBy('donation_date', 'desc')
            ->first();

        if ($recentDonation) {
            $nextEligibleDate = Carbon::parse($recentDonation->donation_date)->addDays(56);
            return response()->json([
                'error' => 'ineligible',
                'message' => "You are not eligible to donate yet. Your last donation was on {$recentDonation->donation_date}. Next eligible date: {$nextEligibleDate->toDateString()}",
            ], 422);
        }

        // Also check last_donation_date field
        if ($user->last_donation_date) {
            $lastDonationDate = Carbon::parse($user->last_donation_date);
            if ($lastDonationDate->greaterThanOrEqualTo($cutoffDate)) {
                $nextEligibleDate = $lastDonationDate->copy()->addDays(56);
                return response()->json([
                    'error' => 'ineligible',
                    'message' => "You are not eligible to donate yet. Last donation date: {$lastDonationDate->toDateString()}. Next eligible date: {$nextEligibleDate->toDateString()}",
                ], 422);
            }
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
        if (!$bloodRequest) {
            return response()->json(['error' => 'not_found', 'message' => 'Blood request not found'], 404);
        }

        // Check if inventory is available for this request
        // If inventory is available, admin handles approval, so we don't change request status
        $inventoryTotal = (int) BloodInventory::where('blood_type', $bloodRequest->blood_type)
            ->sum('quantity_ml');
        $isInventoryAvailable = $inventoryTotal >= $bloodRequest->quantity_ml;

        // If inventory is NOT available (request was sent to donors), check fulfillment status
        if (!$isInventoryAvailable) {
            // Calculate total accepted donations for this request (excluding current donor)
            // Get all accepted matches for this request (excluding current match)
            $acceptedMatches = DonorMatch::where('request_id', $bloodRequest->id)
                ->where('status', 'Accepted')
                ->where('id', '!=', $match->id) // Exclude current match
                ->pluck('donor_id')
                ->toArray();

            // Calculate total donations from accepted donors for this request (excluding current donor)
            $totalAcceptedDonations = Donation::where('request_id', $bloodRequest->id)
                ->whereIn('donor_id', $acceptedMatches)
                ->sum('quantity_ml');

            // Calculate remaining quantity needed
            $remainingQuantity = max(0, $bloodRequest->quantity_ml - $totalAcceptedDonations);

            // Check if request is already fulfilled
            if ($remainingQuantity <= 0) {
                return response()->json([
                    'error' => 'already_fulfilled',
                    'message' => 'This request is already fulfilled. No more donations are needed.',
                ], 400);
            }

            // Get the donation quantity the donor wants to donate (max 450ml per donor)
            $donationQuantity = $validated['quantity_ml'] ?? min(450, $remainingQuantity);

            // Check if this donation would exceed the required quantity
            if ($totalAcceptedDonations + $donationQuantity > $bloodRequest->quantity_ml) {
                $donationQuantity = $remainingQuantity; // Adjust to remaining quantity
            }

            // Create donation record
            $donation = Donation::create([
                'donor_id' => $user->id,
                'blood_type' => $bloodRequest->blood_type ?? null,
                'quantity_ml' => $donationQuantity,
                'donation_date' => $scheduledAt,
                'campaign_id' => null,
                'request_id' => $bloodRequest->id ?? null,
                'location' => $scheduledLocation,
                'verified' => false,
            ]);

            // Update user's last_donation_date to enforce 56-day rule
            $user->last_donation_date = $scheduledAt;
            $user->save();

            // Update match
            $match->status = 'Accepted';
            $match->scheduled_at = $scheduledAt;
            $match->scheduled_location = $scheduledLocation;
            $match->save();

            // Calculate new total after this donation
            $newTotalAccepted = $totalAcceptedDonations + $donationQuantity;
            $newRemainingQuantity = max(0, $bloodRequest->quantity_ml - $newTotalAccepted);

            // Update request status to 'Approved' only when fully fulfilled (if inventory not available)
            if ($bloodRequest->status === 'Pending' && $newRemainingQuantity <= 0) {
                $bloodRequest->status = 'Approved';
                $bloodRequest->save();
            }

            // Notify receiver
            Notification::create([
                'user_id' => $bloodRequest->receiver_id,
                'message' => "Donor {$user->name} accepted donation for request #{$bloodRequest->id}, scheduled at {$scheduledAt}. " .
                    ($newRemainingQuantity > 0 ? "Remaining quantity needed: {$newRemainingQuantity} ml." : "Request is now fulfilled."),
                'type' => 'donation_confirmed',
                'is_read' => false,
            ]);

            return response()->json([
                'message' => 'Donation scheduled',
                'donation' => $donation->load('request.receiver'),
                'remaining_quantity_ml' => $newRemainingQuantity,
                'request_fulfilled' => $newRemainingQuantity <= 0,
            ], 200);
        } else {
            // Inventory is available - admin handles approval, so we just create the donation
            $donationQuantity = $validated['quantity_ml'] ?? min(450, $bloodRequest->quantity_ml ?? 450);

            // Create donation record
            $donation = Donation::create([
                'donor_id' => $user->id,
                'blood_type' => $bloodRequest->blood_type ?? null,
                'quantity_ml' => $donationQuantity,
                'donation_date' => $scheduledAt,
                'campaign_id' => null,
                'request_id' => $bloodRequest->id ?? null,
                'location' => $scheduledLocation,
                'verified' => false,
            ]);

            // Update user's last_donation_date to enforce 56-day rule
            $user->last_donation_date = $scheduledAt;
            $user->save();

            // Update match
            $match->status = 'Accepted';
            $match->scheduled_at = $scheduledAt;
            $match->scheduled_location = $scheduledLocation;
            $match->save();

            // Notify receiver
            Notification::create([
                'user_id' => $bloodRequest->receiver_id,
                'message' => "Donor {$user->name} accepted donation for request #{$bloodRequest->id}, scheduled at {$scheduledAt}.",
                'type' => 'donation_confirmed',
                'is_read' => false,
            ]);

            return response()->json([
                'message' => 'Donation scheduled',
                'donation' => $donation->load('request.receiver')
            ], 200);
        }
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

        // Add donation progress information for each match
        $matches = $matches->map(function ($match) {
            if ($match->request) {
                // Calculate total donated (excluding current donor if they haven't accepted yet)
                $acceptedMatches = DonorMatch::where('request_id', $match->request_id)
                    ->where('status', 'Accepted')
                    ->where('id', '!=', $match->id) // Exclude current match
                    ->pluck('donor_id')
                    ->toArray();

                $totalDonated = Donation::where('request_id', $match->request_id)
                    ->whereIn('donor_id', $acceptedMatches)
                    ->sum('quantity_ml');

                $match->donated_quantity_ml = (int) $totalDonated;
                $match->remaining_quantity_ml = max(0, $match->request->quantity_ml - $totalDonated);
                $match->requested_quantity_ml = $match->request->quantity_ml;
            }
            return $match;
        });

        return response()->json($matches);
    }
}
