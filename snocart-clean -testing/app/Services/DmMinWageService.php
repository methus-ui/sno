<?php

namespace App\Services;

use App\Models\DeliveryMan;
use App\Models\DeliveryManWallet;
use App\Models\DmMinWageSetting;
use App\Models\DmWageAdjustment;
use App\Models\ProvideDMEarning;
use App\Models\AccountTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DmMinWageService
{
    public function calculateDailyAdjustment(DeliveryMan $dm, string $date): ?DmWageAdjustment
    {
        $setting = DmMinWageSetting::active()
            ->where(function ($q) use ($dm) {
                $q->whereNull('zone_id')->orWhere('zone_id', $dm->zone_id);
            })
            ->where(function ($q) use ($dm) {
                $q->where('dm_type', 'all')
                    ->orWhere('dm_type', $dm->type ?? 'zone_wise');
            })
            ->first();

        if (!$setting) return null;

        // Check if already calculated for this date
        $existing = DmWageAdjustment::where('delivery_man_id', $dm->id)
            ->where('date', $date)
            ->first();

        if ($existing) return null;

        // Calculate hours logged from attendances
        $hoursLogged = $this->getHoursLogged($dm->id, $date);

        if ($hoursLogged < $setting->min_hours_required) return null;

        // Calculate total earned that day
        $totalEarned = DB::table('order_transactions')
            ->where('delivery_man_id', $dm->id)
            ->whereDate('created_at', $date)
            ->sum('original_delivery_charge');

        // Add incentive earnings
        $incentiveEarned = ProvideDMEarning::where('delivery_man_id', $dm->id)
            ->where('method', 'incentive')
            ->whereDate('created_at', $date)
            ->sum('amount');

        $totalEarned += $incentiveEarned;

        if ($totalEarned >= $setting->min_daily_amount) return null;

        $adjustment = $setting->min_daily_amount - $totalEarned;

        return DmWageAdjustment::create([
            'delivery_man_id' => $dm->id,
            'date' => $date,
            'total_earned' => $totalEarned,
            'min_guaranteed' => $setting->min_daily_amount,
            'adjustment_amount' => $adjustment,
            'hours_logged' => $hoursLogged,
            'status' => 'pending',
        ]);
    }

    public function processAllAdjustments(string $date): int
    {
        $dms = DeliveryMan::where('active', 1)
            ->where('application_status', 'approved')
            ->get();

        $count = 0;
        foreach ($dms as $dm) {
            $adj = $this->calculateDailyAdjustment($dm, $date);
            if ($adj) {
                $this->payAdjustment($adj);
                $count++;
            }
        }

        return $count;
    }

    public function payAdjustment(DmWageAdjustment $adjustment): void
    {
        $dm = $adjustment->deliveryMan;
        $wallet = DeliveryManWallet::firstOrCreate(
            ['delivery_man_id' => $dm->id],
            ['collected_cash' => 0, 'total_earning' => 0, 'total_withdrawn' => 0, 'pending_withdraw' => 0, 'incentive_earning' => 0]
        );

        $wallet->increment('total_earning', $adjustment->adjustment_amount);

        ProvideDMEarning::create([
            'delivery_man_id' => $dm->id,
            'amount' => $adjustment->adjustment_amount,
            'ref' => 'min_wage_adjustment_' . $adjustment->date->format('Y-m-d'),
            'method' => 'incentive',
            'incentive_type' => 'min_wage',
            'metadata' => [
                'date' => $adjustment->date->format('Y-m-d'),
                'hours_logged' => $adjustment->hours_logged,
                'total_earned' => $adjustment->total_earned,
                'min_guaranteed' => $adjustment->min_guaranteed,
            ],
        ]);

        $adjustment->update(['status' => 'paid']);
    }

    private function getHoursLogged(int $dmId, string $date): float
    {
        $attendances = DB::table('deliveryman_attendances')
            ->where('delivery_man_id', $dmId)
            ->whereDate('created_at', $date)
            ->get();

        $totalMinutes = 0;
        foreach ($attendances as $att) {
            if ($att->punch_in && $att->punch_out) {
                $in = Carbon::parse($att->punch_in);
                $out = Carbon::parse($att->punch_out);
                $totalMinutes += $in->diffInMinutes($out);
            }
        }

        return round($totalMinutes / 60, 2);
    }
}
