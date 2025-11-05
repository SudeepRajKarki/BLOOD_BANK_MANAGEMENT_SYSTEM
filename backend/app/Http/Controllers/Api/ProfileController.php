<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
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

        $user->role = $validated['role'];
        $user->save();

        return response()->json(['message' => 'Role switched', 'role' => $user->role]);
    }

    public function deleteAccount(Request $request)
    {
        $user = Auth::user();

        // Prevent admins from deleting their account
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

        // Optional: delete related records
        \App\Models\Donation::where('donor_id', $user->id)->delete();
        \App\Models\BloodRequest::where('receiver_id', $user->id)->delete();
        \App\Models\DonorMatch::where('donor_id', $user->id)->orWhere('request_id', $user->id)->delete();
        \App\Models\Notification::where('user_id', $user->id)->delete();

        $user->delete();

        return response()->json(['message' => 'Account deleted successfully']);
    }
}