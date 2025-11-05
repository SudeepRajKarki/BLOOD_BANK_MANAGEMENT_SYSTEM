<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\DonationController;
use App\Http\Controllers\Api\RequestController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\AiMatchingController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\BloodInventoryController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\RequestApprovalController;

// Auth routes
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::get('verify-email', [AuthController::class, 'verifyEmail']);
Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
Route::get('reset-password', [AuthController::class, 'verifyResetToken']);
Route::post('reset-password', [AuthController::class, 'resetPassword']);

// Protected routes grouped by role - jwt.auth applied to all
Route::middleware(['jwt.auth'])->group(function () {
    Route::get('profile', [UserController::class, 'profile']);
    Route::post('profile/switch-role', [ProfileController::class, 'switchRole']);
    Route::post('logout', [AuthController::class, 'logout']);

    // role-specific route files
    require __DIR__ . '/admin.php';
    require __DIR__ . '/donor.php';
    require __DIR__ . '/receiver.php';
});
