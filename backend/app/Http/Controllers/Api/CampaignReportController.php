<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CampaignReport;

class CampaignReportController extends Controller
{
    // Admin: list all campaign reports
    public function index()
    {
        $reports = CampaignReport::with('campaign')->orderByDesc('created_at')->get();
        return response()->json($reports);
    }

    // Show a single report
    public function show($id)
    {
        $report = CampaignReport::with('campaign')->findOrFail($id);
        return response()->json($report);
    }
}
