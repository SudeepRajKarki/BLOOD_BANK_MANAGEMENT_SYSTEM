<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Donation;
use Illuminate\Support\Facades\Auth;

class DonationController extends Controller
{
    // Store a new donation
    public function store(Request $request)
    {
        // Get the authenticated user
        $user = Auth::user();

        // ✅ Validate only what frontend sends (campaign_id and quantity)
        $validated = $request->validate([
            'campaign_id' => 'required|exists:campaigns,id',
            'quantity_ml' => 'required|integer|min:100',
        ]);

        // ✅ Create donation using donor's own blood type
        $donation = Donation::create([
            'donor_id' => $user->id,
            'campaign_id' => $validated['campaign_id'],
            'blood_type' => $user->blood_type, // always use donor's blood type
            'quantity_ml' => $validated['quantity_ml'],
            'donation_date' => now(),
            'location' => null,
        ]);

        return response()->json([
            'message' => 'Donation registered successfully.',
            'donation' => $donation,
        ], 201);
    }

    // List all donations by this donor
    public function index()
    {
        $donations = Donation::where('donor_id', Auth::id())->get();
        return response()->json($donations);
    }
}
