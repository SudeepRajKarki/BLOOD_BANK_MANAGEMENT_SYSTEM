<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RequestApprovalController;
use App\Http\Controllers\Api\BloodInventoryController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\RequestController;
use App\Http\Controllers\Api\CampaignReportController;

// Admin routes (jwt.auth applied in api.php). Controllers also enforce admin role when needed.

// Inventory management
Route::get('blood-inventory', [BloodInventoryController::class, 'index']);
Route::post('blood-inventory', [BloodInventoryController::class, 'store']);
Route::put('blood-inventory/{id}', [BloodInventoryController::class, 'update']);
Route::delete('blood-inventory/{id}', [BloodInventoryController::class, 'destroy']);
Route::get('blood-inventory/{id}', [BloodInventoryController::class, 'show']);

// Campaigns
Route::get('campaigns', [CampaignController::class, 'index']);
Route::post('campaign', [CampaignController::class, 'store']);
Route::get('campaigns/{id}', [CampaignController::class, 'show']);
Route::put('campaigns/{id}', [CampaignController::class, 'update']);
Route::delete('campaigns/{id}', [CampaignController::class, 'destroy']);

// Campaign reports (for admin/dashboard)
Route::get('campaign-reports', [CampaignReportController::class, 'index']);
Route::get('campaign-reports/{id}', [CampaignReportController::class, 'show']);

// AI: demand forecasting and campaign targeting (admin)
Route::post('ai/demand-forecast', [\App\Http\Controllers\Api\AiMatchingController::class, 'forecastDemand']);
Route::post('ai/campaign-targeting', [\App\Http\Controllers\Api\AiMatchingController::class, 'recommendCampaigns']);

// Request approvals
Route::get('requests', [RequestController::class, 'index']);
Route::post('requests/{id}/approve', [RequestApprovalController::class, 'approve']);
Route::post('requests/{id}/deny', [RequestApprovalController::class, 'deny']);

// Analytics
Route::get('dashboard', [AnalyticsController::class, 'dashboard']);
