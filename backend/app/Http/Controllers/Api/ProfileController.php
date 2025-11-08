<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Donation;
use App\Models\BloodRequest;
use App\Models\DonorMatch;
use App\Models\Notification;

class ProfileController extends Controller
{
    // 🔹 Fetch the user profile with blood type and other info
   public function profile()
  {
    $user = Auth::user();

    return response()->json([
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'blood_type' => $user->blood_type,
            'created_at' => $user->created_at->toDateString(),
        ]
    ]);
    }


    // 🔹 Switch between donor/receiver roles
    public function switchRole(Request $request)
    {
        $user = Auth::user();

        // Prevent admins from switching role
        if ($user->role === 'admin') {
            return response()->json([
                'message' => 'Admin role cannot be changed.'
            ], 403);
        }

        $validated = $request->validate([
            'role' => 'required|in:donor,receiver',
        ]);

        // If switching to donor, ensure blood type is filled
        if ($validated['role'] === 'donor' && !$user->blood_type) {
            return response()->json([
                'message' => 'Please set your blood type before becoming a donor.'
            ], 400);
        }

        $user->role = $validated['role'];
        $user->save();

        return response()->json(['message' => 'Role switched', 'role' => $user->role]);
    }

    // 🔹 Restrict donor to donate only their blood type
    public function checkDonationEligibility(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'donor') {
            return response()->json(['eligible' => false, 'message' => 'Only donors can donate blood.']);
        }

        if (!$user->blood_type) {
            return response()->json(['eligible' => false, 'message' => 'Blood type not set in profile.']);
        }

        $requestedType = $request->input('blood_type');

        if ($requestedType !== $user->blood_type) {
            return response()->json([
                'eligible' => false,
                'message' => "You can only donate your own blood type ({$user->blood_type})."
            ]);
        }

        return response()->json(['eligible' => true, 'message' => 'Eligible to donate.']);
    }

    // 🔹 Delete account (with protection for admins)
    public function deleteAccount(Request $request)
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            return response()->json([
                'message' => 'Admin accounts cannot be deleted.'
            ], 403);
        }

        $request->validate([
            'password' => 'required',
        ]);

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Incorrect password.'], 403);
        }

        Donation::where('donor_id', $user->id)->delete();
        BloodRequest::where('receiver_id', $user->id)->delete();
        DonorMatch::where('donor_id', $user->id)->orWhere('request_id', $user->id)->delete();
        Notification::where('user_id', $user->id)->delete();

        $user->delete();

        return response()->json(['message' => 'Account deleted successfully']);
    }
}
