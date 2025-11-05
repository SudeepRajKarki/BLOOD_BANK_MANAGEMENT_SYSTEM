<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::where('user_id', Auth::id())->get();
        return response()->json($notifications);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'type' => 'required|string',
        ]);
        $notification = Notification::create([
            'user_id' => Auth::id(),
            'message' => $validated['message'],
            'type' => $validated['type'],
        ]);
        return response()->json($notification, 201);
    }
}
