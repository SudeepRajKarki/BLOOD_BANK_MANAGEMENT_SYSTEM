<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BloodRequest;
use App\Models\BloodInventory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Notification;

class RequestApprovalController extends Controller
{
    public function allRequests()
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            return response()->json(['error' => 'forbidden'], 403);
        }

        // Only show requests that were sent to admin (i.e., inventory was available)
        // Requests sent to donors (inventory insufficient) should NOT appear here
        //
        // Logic: If a request has donor matches, it was sent to donors (inventory was insufficient)
        //        If a request has no donor matches, check if inventory is sufficient - if yes, it was sent to admin
        $allRequests = BloodRequest::with(['receiver', 'donorMatches'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Filter requests: Only include those that should be shown to admin
        // (i.e., requests where inventory was available and no donor matches exist)
        $adminRequests = $allRequests->filter(function ($request) {
            $donorMatchesCount = $request->donorMatches ? $request->donorMatches->count() : 0;

            // Check current inventory for this blood type
            $inventoryTotal = (int) BloodInventory::where('blood_type', $request->blood_type)
                ->sum('quantity_ml');

            // Rule 1: If request has donor matches, it was sent to donors (inventory was insufficient)
            // So exclude it from admin approval list
            if ($donorMatchesCount > 0) {
                return false;
            }

            // Rule 2: If no donor matches AND inventory is insufficient,
            // it was likely sent to donors but no matches were found (or matching failed)
            // So exclude it from admin approval list
            if ($inventoryTotal < $request->quantity_ml) {
                return false;
            }

            // Rule 3: If no donor matches AND inventory is sufficient,
            // it was sent to admin, so include it in admin approval list
            return true;
        })->values(); // Reset array keys

        return response()->json($adminRequests);
    }

    public function approve($id)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            return response()->json(['error' => 'forbidden'], 403);
        }

        return DB::transaction(function () use ($id) {
            $req = BloodRequest::lockForUpdate()->findOrFail($id);

            if ($req->status !== 'Pending') {
                return response()->json([
                    'status' => 'invalid',
                    'message' => 'Request is not pending.'
                ], 400);
            }

            // 🔴 Removed location filter — check only by blood type
            $inventory = BloodInventory::where('blood_type', $req->blood_type)
                ->lockForUpdate()
                ->first();

            if (!$inventory) {
                $req->status = 'Rejected';
                $req->save();
                Notification::create([
                    'user_id' => $req->receiver_id,
                    'message' => "No inventory found for {$req->blood_type}.",
                    'type' => 'request_denied',
                    'is_read' => false,
                ]);
                return response()->json([
                    'status' => 'rejected',
                    'message' => 'No matching inventory found.'
                ], 200);
            }

            if ($inventory->quantity_ml < $req->quantity_ml) {
                $req->status = 'Rejected';
                $req->save();

                Notification::create([
                    'user_id' => $req->receiver_id,
                    'message' => "Insufficient inventory for your blood request #{$req->id}.",
                    'type' => 'request_denied',
                    'is_read' => false,
                ]);

                return response()->json([
                    'status' => 'rejected',
                    'message' => 'Not enough inventory; request denied.'
                ], 200);
            }

            // ✅ Enough inventory — Approve
            $inventory->quantity_ml -= $req->quantity_ml;
            $inventory->save();

            $req->status = 'Approved';
            $req->save();

            Notification::create([
                'user_id' => $req->receiver_id,
                'message' => "Your blood request #{$req->id} has been approved!",
                'type' => 'request_approved',
                'is_read' => false,
            ]);

            return response()->json([
                'status' => 'approved',
                'message' => 'Request approved successfully and inventory updated.'
            ], 200);
        });
    }

    public function deny($id)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            return response()->json(['error' => 'forbidden'], 403);
        }

        $req = BloodRequest::findOrFail($id);
        $req->status = 'Rejected';
        $req->save();

        Notification::create([
            'user_id' => $req->receiver_id,
            'message' => "Your blood request #{$req->id} has been denied by admin.",
            'type' => 'request_denied',
            'is_read' => false,
        ]);

        return response()->json([
            'status' => 'rejected',
            'message' => 'Request denied by admin.'
        ], 200);
    }
}
