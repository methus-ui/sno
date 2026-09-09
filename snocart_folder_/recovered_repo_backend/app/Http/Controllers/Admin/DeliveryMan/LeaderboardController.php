<?php

namespace App\Http\Controllers\Admin\DeliveryMan;

use App\Models\DmLeaderboard;
use App\Models\Zone;
use App\Services\DmRankingService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LeaderboardController extends Controller
{
    public function index(Request $request)
    {
        $periodType = $request->get('period_type', 'daily');
        $zoneId = $request->get('zone_id');

        $service = new DmRankingService();
        $leaderboard = $service->getLeaderboard($periodType, $zoneId, 100);
        $zones = Zone::all();

        return view('admin-views.delivery-man.leaderboard.index', compact('leaderboard', 'zones', 'periodType', 'zoneId'));
    }

    public function generateLeaderboard(Request $request)
    {
        $request->validate([
            'period_type' => 'required|in:daily,weekly,monthly',
        ]);

        $service = new DmRankingService();
        $count = $service->generateLeaderboard($request->period_type, $request->zone_id);

        return back()->with('success', "Leaderboard generated with {$count} entries.");
    }
}
