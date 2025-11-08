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

        $requests = BloodRequest::with('receiver')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($requests);
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
