<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DonationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\DonorMatchController;

// Donor routes (jwt.auth applied in api.php). Controllers enforce donor role as needed.



// Notifications
Route::get('notifications', [NotificationController::class, 'index']);
Route::post('notifications', [NotificationController::class, 'store']);

// Donor matches: list and accept
Route::get('donor-matches', [\App\Http\Controllers\Api\DonorMatchController::class, 'index']);
Route::post('donor-matches/{id}/accept', [\App\Http\Controllers\Api\DonorMatchController::class, 'accept']);
Route::post('donor-matches/{id}/decline', [\App\Http\Controllers\Api\DonorMatchController::class, 'decline']);

// Campaigns available to donors
Route::get('active-campaigns', [\App\Http\Controllers\Api\CampaignController::class, 'active']);
