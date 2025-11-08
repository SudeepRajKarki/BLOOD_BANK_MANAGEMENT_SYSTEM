<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RequestApprovalController;
use App\Http\Controllers\Api\BloodInventoryController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\RequestController;
use App\Http\Controllers\Api\CampaignReportController;

// 🔒 Protected inventory routes (admin only - inside jwt.auth from api.php)
Route::post('blood-inventory', [BloodInventoryController::class, 'store']);
Route::put('blood-inventory/{id}', [BloodInventoryController::class, 'update']);
Route::delete('blood-inventory/{id}', [BloodInventoryController::class, 'destroy']);

// Campaigns (admin only)
Route::get('campaigns', [CampaignController::class, 'index']);
Route::post('campaign', [CampaignController::class, 'store']);
Route::get('campaigns/{id}', [CampaignController::class, 'show']);
Route::put('campaigns/{id}', [CampaignController::class, 'update']);
Route::delete('campaigns/{id}', [CampaignController::class, 'destroy']);

// Campaign reports (admin only)
Route::get('campaign-reports', [CampaignReportController::class, 'index']);
Route::get('campaign-reports/{id}', [CampaignReportController::class, 'show']);

// Request approvals (admin only)
// Route::get('requests', [RequestController::class, 'index']);
Route::prefix('admin')->group(function () {
    Route::get('requests', [RequestApprovalController::class, 'allRequests']); // GET /admin/requests
    Route::post('requests/{id}/approve', [RequestApprovalController::class, 'approve']);
    Route::post('requests/{id}/deny', [RequestApprovalController::class, 'deny']);
});
// Analytics (admin only)
Route::get('dashboard', [AnalyticsController::class, 'dashboard']);

// AI: demand forecasting and campaign targeting (admin only)
Route::post('ai/demand-forecast', [\App\Http\Controllers\Api\AiMatchingController::class, 'forecastDemand']);
Route::post('ai/campaign-targeting', [\App\Http\Controllers\Api\AiMatchingController::class, 'recommendCampaigns']);
