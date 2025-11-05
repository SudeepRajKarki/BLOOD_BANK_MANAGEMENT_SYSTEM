<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
class ProfileController extends Controller
{
    public function switchRole(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'role' => 'required|in:donor,receiver',
        ]);
        $user->role = $validated['role'];
        $user->save();
        return response()->json(['message' => 'Role switched', 'role' => $user->role]);
    }
}
 