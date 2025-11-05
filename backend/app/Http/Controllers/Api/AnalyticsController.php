<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\BloodRequest;
use App\Models\Campaign;
use Illuminate\Http\Request;
class AnalyticsController extends Controller
{
    public function dashboard()
    {
        $donations = Donation::count();
        $requests = BloodRequest::count();
        $campaigns = Campaign::count();
        $topDonors = Donation::selectRaw('donor_id, COUNT(*) as total')
            ->groupBy('donor_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
        return response()->json([
            'donations' => $donations,
            'requests' => $requests,
            'campaigns' => $campaigns,
            'top_donors' => $topDonors,
        ]);
    }
}
