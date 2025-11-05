<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function profile(Request $request)
    {
        return response()->json(['user' => $request->user()]);
    }

    public function listDonors()
    {
        $donors = User::where('role', 'donor')->get();
        return response()->json($donors);
    }

    public function listReceivers()
    {
        $receivers = User::where('role', 'receiver')->get();
        return response()->json($receivers);
    }
}
