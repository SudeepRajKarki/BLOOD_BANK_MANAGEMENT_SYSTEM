<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\BloodInventory;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CampaignController extends Controller
{
    public function index()
    {
        $campaigns = Campaign::all();
        return response()->json($campaigns);
    }

    // Return only ongoing/active campaigns for donors
    public function active()
    {
        $campaigns = Campaign::where('status', 'Ongoing')->get();
        return response()->json($campaigns);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'location' => 'required|string',
            'date' => 'required|date',
            'status' => 'required|string',
        ]);

        $campaign = Campaign::create([
            'location' => $validated['location'],
            'date' => $validated['date'],
            'created_by' => Auth::id(),
            'status' => $validated['status'],
            'description' => $request->input('description'),
        ]);

        return response()->json($campaign, 201);
    }

    public function show($id)
    {
        $campaign = Campaign::findOrFail($id);
        return response()->json($campaign);
    }

    public function update(Request $request, $id)
    {
        $campaign = Campaign::findOrFail($id);
        $validated = $request->validate([
            'location' => 'sometimes|required|string',
            'date' => 'sometimes|required|date',
            'status' => 'sometimes|required|string',
            'description' => 'nullable|string',
        ]);

        $oldStatus = $campaign->status;
        $campaign->update($validated);

        // If campaign has just been marked Completed, generate report, update inventory and notify admins
        if (isset($validated['status']) && $validated['status'] === 'Completed' && $oldStatus !== 'Completed') {
            DB::transaction(function () use ($campaign) {
                $donations = Donation::where('campaign_id', $campaign->id)->get();

                $totalQuantity = $donations->sum('quantity_ml');

                $donors = $donations->map(function ($d) {
                    return [
                        'donor_id' => $d->donor_id,
                        'name' => $d->donor ? $d->donor->name : null,
                        'quantity_ml' => $d->quantity_ml,
                        'blood_type' => $d->blood_type,
                    ];
                })->unique('donor_id')->values();

                $byType = $donations->groupBy('blood_type')->map(function ($group) {
                    return $group->sum('quantity_ml');
                })->toArray();

                // Update or create inventory records at the central inventory location
                // Use env CENTR AL_INVENTORY_LOCATION or default to 'Central Bank'
                $inventoryLocation = env('CENTRAL_INVENTORY_LOCATION', 'Central Bank');
                foreach ($byType as $bloodType => $qty) {
                    if (empty($bloodType) || $qty <= 0) {
                        continue;
                    }
                    $inv = BloodInventory::where('blood_type', $bloodType)
                        ->where('location', $inventoryLocation)
                        ->first();
                    if ($inv) {
                        $inv->quantity_ml = $inv->quantity_ml + $qty;
                        $inv->save();
                    } else {
                        BloodInventory::create([
                            'blood_type' => $bloodType,
                            'quantity_ml' => $qty,
                            'location' => $inventoryLocation,
                        ]);
                    }
                }

                // Build report text
                $reportLines = [];
                $reportLines[] = "Campaign #{$campaign->id} completed on {$campaign->date} at {$campaign->location}";
                $reportLines[] = "Total quantity (ml): {$totalQuantity}";
                $reportLines[] = "By blood type:";
                foreach ($byType as $bt => $q) {
                    $reportLines[] = " - {$bt}: {$q} ml";
                }
                $reportLines[] = "Donors (unique):";
                foreach ($donors as $d) {
                    $reportLines[] = " - {$d['donor_id']} {$d['name']} ({$d['blood_type']}) - {$d['quantity_ml']} ml";
                }

                $reportText = implode("\n", $reportLines);

                // Persist report to campaign_reports table (for dashboard)
                \App\Models\CampaignReport::create([
                    'campaign_id' => $campaign->id,
                    'report_text' => $reportText,
                    'total_quantity_ml' => $totalQuantity,
                    'by_type' => json_encode($byType),
                    'donors' => json_encode($donors->toArray()),
                    'created_by' => Auth::id(),
                ]);
            });
        }

        return response()->json(['message' => 'Updated', 'data' => $campaign]);
    }

    public function destroy($id)
    {
        Campaign::destroy($id);
        return response()->json(['message' => 'Deleted']);
    }
}
