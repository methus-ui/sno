<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryMan;
use App\Models\DeliverymanAttendance;
use App\Models\BusinessSetting;
use App\Services\DeliverymanAttendanceService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class DeliverymanOfflineMonitorController extends Controller
{
    /**
     * Dashboard - Show DMs with offline issues today
     */
    public function index(Request $request)
    {
        try {
            $today = Carbon::today()->toDateString();

            // Get all attendance records for today
            $attendances = DeliverymanAttendance::with(['deliveryMan'])
                ->whereDate('date', $today)
                ->orderByDesc('total_offline_minutes')
                ->paginate(20);

            // Statistics with safer queries
            $stats = [
                'total_dms_online_today' => DeliverymanAttendance::whereDate('date', $today)->count(),
                'dms_exceeded_threshold' => DeliverymanAttendance::whereDate('date', $today)
                    ->where('incentive_eligible', 0)
                    ->count(),
                'dms_at_risk' => DeliverymanAttendance::whereDate('date', $today)
                    ->where('incentive_eligible', 1)
                    ->where('total_offline_minutes', '>=', 3)
                    ->count(),
                'avg_offline_time' => round(DeliverymanAttendance::whereDate('date', $today)
                    ->avg('total_offline_minutes') ?? 0, 2),
            ];

            return view('admin-views.delivery-man.offline-monitor.index', compact('attendances', 'stats'));
        } catch (\Exception $e) {
            // Log the full error
            \Log::error('Offline Monitor Index Error: ' . $e->getMessage());
            \Log::error('File: ' . $e->getFile() . ' Line: ' . $e->getLine());
            \Log::error($e->getTraceAsString());

            // Return error message directly for debugging
            return response()->json([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Show detailed offline sessions for a specific DM
     */
    public function show(Request $request, $id)
    {
        $dm = DeliveryMan::with(['rating', 'wallet'])->findOrFail($id);

        $dateFrom = $request->get('from', Carbon::now()->subDays(7)->toDateString());
        $dateTo = $request->get('to', Carbon::today()->toDateString());

        // Get attendance records for date range
        $attendances = DeliverymanAttendance::where('delivery_man_id', $id)
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->orderByDesc('date')
            ->get();

        // Calculate summary
        $summary = [
            'total_days' => $attendances->count(),
            'days_exceeded' => $attendances->where('incentive_eligible', false)->count(),
            'total_offline_minutes' => $attendances->sum('total_offline_minutes'),
            'avg_offline_per_day' => $attendances->avg('total_offline_minutes') ?? 0,
            'max_offline_day' => $attendances->max('total_offline_minutes') ?? 0,
        ];

        return view('admin-views.delivery-man.offline-monitor.show', compact('dm', 'attendances', 'summary', 'dateFrom', 'dateTo'));
    }

    /**
     * Live monitoring - Real-time offline status
     */
    public function liveMonitor()
    {
        $today = Carbon::today()->toDateString();

        // Get currently active DMs with their offline status
        $activeDms = DeliveryMan::with('todayAttendance')
            ->where('active', 1)
            ->get()
            ->map(function($dm) {
                $attendance = $dm->todayAttendance;

                return [
                    'id' => $dm->id,
                    'name' => $dm->f_name . ' ' . $dm->l_name,
                    'phone' => $dm->phone,
                    'is_offline' => $attendance ? ($attendance->is_currently_offline ?? false) : false,
                    'offline_time' => $attendance ? ($attendance->total_offline_minutes ?? 0) : 0,
                    'threshold' => DeliverymanAttendanceService::OFFLINE_THRESHOLD_MINUTES,
                    'incentive_eligible' => $attendance ? ($attendance->incentive_eligible ?? true) : true,
                    'last_offline_at' => $attendance ? $attendance->last_offline_at : null,
                    'current_offline_duration' => ($attendance && $attendance->is_currently_offline && $attendance->last_offline_at)
                        ? Carbon::parse($attendance->last_offline_at)->diffInMinutes(Carbon::now())
                        : 0,
                ];
            });

        return view('admin-views.delivery-man.offline-monitor.live', compact('activeDms'));
    }

    /**
     * Generate daily report
     */
    public function dailyReport(Request $request)
    {
        $date = $request->get('date', Carbon::today()->toDateString());

        $attendances = DeliverymanAttendance::with('deliveryMan')
            ->whereDate('date', $date)
            ->orderByDesc('total_offline_minutes')
            ->get();

        // Group by eligibility
        $ineligible = $attendances->where('incentive_eligible', false);
        $atRisk = $attendances->where('incentive_eligible', true)
            ->where('total_offline_minutes', '>=', 3);
        $good = $attendances->where('incentive_eligible', true)
            ->where('total_offline_minutes', '<', 3);

        return view('admin-views.delivery-man.offline-monitor.daily-report', compact(
            'date', 'attendances', 'ineligible', 'atRisk', 'good'
        ));
    }

    /**
     * Export report as Excel
     */
    public function exportReport(Request $request)
    {
        $dateFrom = $request->get('from', Carbon::now()->subDays(7)->toDateString());
        $dateTo = $request->get('to', Carbon::today()->toDateString());

        $attendances = DeliverymanAttendance::with('deliveryMan')
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->orderBy('date', 'desc')
            ->get();

        $data = [];
        foreach ($attendances as $attendance) {
            $data[] = [
                'Date' => $attendance->date->format('Y-m-d'),
                'DM ID' => $attendance->delivery_man_id,
                'DM Name' => $attendance->deliveryMan->f_name . ' ' . $attendance->deliveryMan->l_name,
                'Phone' => $attendance->deliveryMan->phone,
                'Punch In' => $attendance->punch_in_time,
                'Punch Out' => $attendance->punch_out_time,
                'Working Hours' => $attendance->working_hours,
                'Offline Count' => $attendance->offline_count ?? 0,
                'Total Offline (min)' => $attendance->total_offline_minutes ?? 0,
                'Incentive Eligible' => $attendance->incentive_eligible ? 'Yes' : 'No',
                'Status' => $attendance->status,
            ];
        }

        return response()->json([
            'data' => $data,
            'filename' => "offline_report_{$dateFrom}_to_{$dateTo}.csv"
        ]);
    }

    /**
     * Get offline trends (for charts)
     */
    public function trends(Request $request)
    {
        $days = $request->get('days', 7);
        $dateFrom = Carbon::now()->subDays($days)->toDateString();

        $trends = DeliverymanAttendance::select(
            DB::raw('DATE(date) as day'),
            DB::raw('COUNT(*) as total_dms'),
            DB::raw('SUM(CASE WHEN incentive_eligible = 0 THEN 1 ELSE 0 END) as exceeded_count'),
            DB::raw('AVG(total_offline_minutes) as avg_offline_time')
        )
        ->where('date', '>=', $dateFrom)
        ->groupBy('day')
        ->orderBy('day')
        ->get();

        return response()->json($trends);
    }

    /**
     * Show settings page
     */
    public function settings()
    {
        // Get current settings
        $thresholdSetting = BusinessSetting::where('key', 'dm_offline_threshold')->first();
        $breakEnabledSetting = BusinessSetting::where('key', 'dm_break_time_enabled')->first();
        $breakMinutesSetting = BusinessSetting::where('key', 'dm_break_time_minutes')->first();

        $threshold = $thresholdSetting ? json_decode($thresholdSetting->value, true)['minutes'] : 5;
        $break_enabled = $breakEnabledSetting ? json_decode($breakEnabledSetting->value, true)['status'] : 0;
        $break_minutes = $breakMinutesSetting ? json_decode($breakMinutesSetting->value, true)['minutes'] : 15;

        return view('admin-views.delivery-man.offline-monitor.settings', compact('threshold', 'break_enabled', 'break_minutes'));
    }

    /**
     * Update settings
     */
    public function settingsUpdate(Request $request)
    {
        $request->validate([
            'offline_threshold' => 'required|integer|min:1|max:60',
            'break_time_enabled' => 'nullable|boolean',
            'break_time_minutes' => 'nullable|integer|min:0|max:120',
        ]);

        // Update offline threshold
        BusinessSetting::updateOrCreate(
            ['key' => 'dm_offline_threshold'],
            ['value' => json_encode(['minutes' => $request->offline_threshold])]
        );

        // Update break time enabled
        BusinessSetting::updateOrCreate(
            ['key' => 'dm_break_time_enabled'],
            ['value' => json_encode(['status' => $request->break_time_enabled ? 1 : 0])]
        );

        // Update break time minutes
        if ($request->break_time_enabled) {
            BusinessSetting::updateOrCreate(
                ['key' => 'dm_break_time_minutes'],
                ['value' => json_encode(['minutes' => $request->break_time_minutes ?? 15])]
            );
        }

        // Clear cache to apply new settings
        Artisan::call('cache:clear');

        return redirect()->route('admin.deliveryman.offline-monitor.settings')
            ->with('success', translate('messages.settings_updated_successfully'));
    }
}
