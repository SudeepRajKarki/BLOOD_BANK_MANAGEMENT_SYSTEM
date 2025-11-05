<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RequestController;
use App\Http\Controllers\Api\AiMatchingController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\BloodInventoryController;

// Receiver routes (jwt.auth applied in api.php). Controllers enforce receiver role as needed.

// Requests
Route::post('request', [RequestController::class, 'store']);
Route::get('requests', [RequestController::class, 'index']);

// View inventory
Route::get('blood-inventory', [BloodInventoryController::class, 'index']);

// AI endpoints (proxy) - receiver can invoke matching if needed
Route::post('ai/match-donor', [AiMatchingController::class, 'matchDonor']);
Route::post('ai/predict-priority', [AiMatchingController::class, 'predictPriority']);
Route::post('ai/churn-predict', [AiMatchingController::class, 'predictChurn']);
