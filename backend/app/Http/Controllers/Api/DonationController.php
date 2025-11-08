<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Donation;
use App\Models\Campaign;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DonationController extends Controller
{
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'campaign_id' => 'required|exists:campaigns,id',
            'quantity_ml' => 'required|integer|min:100',
        ]);

        // Check eligibility: last donation must be > 56 days ago
        if ($user->last_donation_date) {
            $lastDonation = Carbon::parse($user->last_donation_date);
            $nextEligibleDate = $lastDonation->addDays(56);

            if (Carbon::now()->lt($nextEligibleDate)) {
                return response()->json([
                    'error' => 'ineligible',
                    'message' => "You are not eligible to donate yet. Next eligible date: {$nextEligibleDate->toDateString()}",
                ], 422);
            }
        }

        // Get campaign to fetch location
        $campaign = Campaign::find($validated['campaign_id']);

        // Create donation record
        $donation = Donation::create([
            'donor_id'     => $user->id,
            'campaign_id'  => $campaign->id,
            'blood_type'   => $user->blood_type,
            'quantity_ml'  => $validated['quantity_ml'],
            'donation_date'=> now(),
            'location'     => $campaign->location, // store campaign location
            'request_id'   => null, // this is not a request-based donation
            'verified'     => false,
        ]);

        // Update last_donation_date
        $user->last_donation_date = now();
        $user->save();

        return response()->json([
            'message' => 'Donation registered successfully.',
            'donation' => $donation,
        ], 201);
    }

    public function index()
    {
        // Load request with receiver and campaign relationship
        $donations = Donation::with(['request.receiver', 'campaign'])->where('donor_id', auth()->id())->get();
        return response()->json($donations);
    }
}
