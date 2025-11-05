<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BloodRequest;
use App\Models\BloodInventory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Notification;
use Illuminate\Support\Facades\Mail;

class RequestApprovalController extends Controller
{
    public function approve($id)
    {
        $user = Auth::user();
        if (! $user || $user->role !== 'admin') {
            return response()->json(['error' => 'forbidden'], 403);
        }

        return DB::transaction(function () use ($id) {
            $req = BloodRequest::lockForUpdate()->findOrFail($id);
            if ($req->status !== 'Pending') {
                return response()->json(['error' => 'invalid', 'message' => 'Request is not pending'], 400);
            }

            // Try to find inventory matching blood type and location
            $invQuery = BloodInventory::where('blood_type', $req->blood_type);
            if (! empty($req->location)) {
                $invQuery->where('location', $req->location);
            }
            $inventory = $invQuery->lockForUpdate()->first();

            if (! $inventory || $inventory->quantity_ml < $req->quantity_ml) {
                // not enough inventory; cannot approve
                // 'Denied' isn't a value in the DB enum for status; use 'Rejected' which is allowed
                $req->status = 'Rejected';
                $req->save();
                // notify receiver
                Notification::create([
                    'user_id' => $req->receiver_id,
                    'message' => "Your blood request #{$req->id} was denied due to insufficient inventory.",
                    'type' => 'request_denied',
                    'is_read' => false,
                ]);
                try {
                    $receiver = $req->receiver;
                    if ($receiver && $receiver->email) {
                        Mail::raw("Your blood request #{$req->id} was denied due to insufficient inventory.", function ($m) use ($receiver) {
                            $m->to($receiver->email)->subject('Blood Request Update');
                        });
                    }
                } catch (\Exception $e) {}

                return response()->json(['message' => 'Not enough inventory; request denied'], 200);
            }

            // decrement inventory and approve
            $inventory->quantity_ml = max(0, $inventory->quantity_ml - $req->quantity_ml);
            $inventory->save();

            $req->status = 'Approved';
            $req->save();

            // notify receiver
            Notification::create([
                'user_id' => $req->receiver_id,
                'message' => "Your blood request #{$req->id} has been approved and will be fulfilled from inventory.",
                'type' => 'request_approved',
                'is_read' => false,
            ]);
            try {
                $receiver = $req->receiver;
                if ($receiver && $receiver->email) {
                    Mail::raw("Your blood request #{$req->id} has been approved and will be fulfilled from inventory.", function ($m) use ($receiver) {
                        $m->to($receiver->email)->subject('Blood Request Approved');
                    });
                }
            } catch (\Exception $e) {}

            return response()->json(['message' => 'Request approved and inventory decremented'], 200);
        });
    }

    public function deny($id)
    {
        $user = Auth::user();
        if (! $user || $user->role !== 'admin') {
            return response()->json(['error' => 'forbidden'], 403);
        }

    $req = BloodRequest::findOrFail($id);
    // use 'Rejected' to match DB enum
    $req->status = 'Rejected';
        $req->save();

        Notification::create([
            'user_id' => $req->receiver_id,
            'message' => "Your blood request #{$req->id} has been denied by admin.",
            'type' => 'request_denied',
            'is_read' => false,
        ]);
        try {
            $receiver = $req->receiver;
            if ($receiver && $receiver->email) {
                Mail::raw("Your blood request #{$req->id} has been denied by admin.", function ($m) use ($receiver) {
                    $m->to($receiver->email)->subject('Blood Request Denied');
                });
            }
        } catch (\Exception $e) {}

        return response()->json(['message' => 'Request denied'], 200);
    }
}
