<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:donor,receiver',
            'blood_type' => 'nullable|string|max:3',
            'location' => 'nullable|string',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'blood_type' => $validated['blood_type'] ?? null,
            'location' => $validated['location'] ?? null,
        ]);

        $token = sha1(time() . $user->email);
        $user->email_verification_token = $token;
        $user->save();

        $verificationUrl = config('app.frontend_url') . '/verify-email?token=' . $token;

        \Mail::to($user->email)->send(new \App\Mail\VerifyEmail($user, $verificationUrl));

        return response()->json(['message' => 'Registration successful. Please verify your email.'], 201);
    }
    // Email verification endpoint
    public function verifyEmail(Request $request)
    {
        $token = $request->query('token');
        $user = User::where('email_verification_token', $token)->first();
        if (!$user) {
            return response()->json(['error' => 'Invalid verification token'], 400);
        }
        $user->email_verified_at = now();
        $user->email_verification_token = null;
        $user->save();
        return response()->json(['message' => 'Email verified successfully']);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }
        return response()->json([
            'user' => Auth::user(),
            'message' => 'Login successful',
            'token' => $token]);
    }

    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Step 1: Send Password Reset Email
     */
    public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $validated['email'])->first();

        // Generate token and expiration
        $token = sha1(time() . $user->email);
        $user->password_reset_token = $token;
        $user->password_reset_expires_at = now()->addMinutes(30); // expires in 30 minutes
        $user->save();

        // Create reset link
        // $resetUrl = url('/api/reset-password?token=' . $token);
        $resetUrl = config('app.frontend_url') . '/reset-password?token=' . $token;

        // Send email
        Mail::to($user->email)->send(new \App\Mail\ResetPasswordMail($user, $resetUrl));

        return response()->json(['message' => 'Password reset email sent successfully.']);
    }

    /**
     * Step 2: Verify Reset Token (optional API endpoint)
     */
    public function verifyResetToken(Request $request)
    {
        $token = $request->query('token');
        $user = User::where('password_reset_token', $token)
                    ->where('password_reset_expires_at', '>', now())
                    ->first();

        if (!$user) {
            return response()->json(['error' => 'Invalid or expired reset token'], 400);
        }

        return response()->json(['message' => 'Token is valid', 'email' => $user->email]);
    }

    /**
     * Step 3: Set New Password
     */
    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::where('password_reset_token', $validated['token'])
                    ->where('password_reset_expires_at', '>', now())
                    ->first();

        if (!$user) {
            return response()->json(['error' => 'Invalid or expired token'], 400);
        }

        $user->password = Hash::make($validated['password']);
        $user->password_reset_token = null;
        $user->password_reset_expires_at = null;
        $user->save();

        return response()->json(['message' => 'Password reset successful. You can now log in.']);
    }
}
