<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Donation;
use Illuminate\Support\Facades\Auth;

class DonationController extends Controller
{
    public function index()
    {
        $donations = Donation::with('donor')->get();
        return response()->json($donations);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'blood_type' => 'required_without:campaign_id|string|max:3',
            'quantity_ml' => 'required|integer|min:1',
            'donation_date' => 'required_without:campaign_id|date',
            'campaign_id' => 'nullable|integer|exists:campaigns,id',
            'location' => 'nullable|string',
        ]);

        $campaignId = $validated['campaign_id'] ?? null;
        if ($campaignId) {
            $campaign = \App\Models\Campaign::find($campaignId);
            if (! $campaign || $campaign->status !== 'Ongoing') {
                return response()->json(['error' => 'invalid_campaign', 'message' => 'Campaign must be Ongoing to donate in-person'], 400);
            }
            // For campaign donations, use the campaign's date and location as authoritative
            $validated['donation_date'] = $campaign->date;
            $validated['location'] = $campaign->location;
        }

        $donation = Donation::create([
            'donor_id' => Auth::id(),
            'blood_type' => $validated['blood_type'] ?? ($campaign->blood_type ?? null),
            'quantity_ml' => $validated['quantity_ml'],
            'donation_date' => $validated['donation_date'] ?? now(),
            'campaign_id' => $campaignId,
            'location' => $validated['location'] ?? null,
        ]);

        return response()->json($donation, 201);
    }
}
