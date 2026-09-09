<?php

namespace App\Services;

use App\Models\DeliveryMan;
use App\Models\DeliveryManWallet;
use App\Models\DmReferralBonus;
use App\Models\ProvideDMEarning;
use App\Models\AccountTransaction;
use App\CentralLogics\Helpers;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DmReferralService
{
    const DEFAULT_BONUS_AMOUNT = 500;
    const DEFAULT_MIN_ORDERS = 10;

    public function generateRefCode(DeliveryMan $dm): string
    {
        if ($dm->ref_code) return $dm->ref_code;

        do {
            $code = strtoupper(Str::random(8));
        } while (DeliveryMan::where('ref_code', $code)->exists());

        $dm->ref_code = $code;
        $dm->save();
        return $code;
    }

    public function applyReferral(DeliveryMan $newDm, string $refCode): bool
    {
        $referrer = DeliveryMan::where('ref_code', $refCode)->first();
        if (!$referrer || $referrer->id === $newDm->id) return false;

        $newDm->referred_by = $referrer->id;
        $newDm->save();

        $bonusAmount = (float) DB::table('business_settings')
            ->where('key', 'dm_referral_bonus_amount')
            ->value('value') ?: self::DEFAULT_BONUS_AMOUNT;

        DmReferralBonus::create([
            'referrer_id' => $referrer->id,
            'referred_id' => $newDm->id,
            'bonus_amount' => $bonusAmount,
            'status' => 'pending',
            'condition_met' => false,
        ]);

        return true;
    }

    public function checkAndPayBonus(DeliveryMan $referredDm): void
    {
        if (!$referredDm->referred_by) return;

        $minOrders = (int) DB::table('business_settings')
            ->where('key', 'dm_referral_min_orders')
            ->value('value') ?: self::DEFAULT_MIN_ORDERS;

        $completedOrders = \App\Models\Order::where('delivery_man_id', $referredDm->id)
            ->where('order_status', 'delivered')
            ->count();

        if ($completedOrders < $minOrders) return;

        $bonus = DmReferralBonus::where('referred_id', $referredDm->id)
            ->where('status', 'pending')
            ->where('condition_met', false)
            ->first();

        if (!$bonus) return;

        $bonus->update(['condition_met' => true, 'status' => 'paid', 'paid_at' => now()]);

        // Credit referrer wallet
        $referrer = DeliveryMan::find($bonus->referrer_id);
        if (!$referrer) return;

        $wallet = DeliveryManWallet::firstOrCreate(
            ['delivery_man_id' => $referrer->id],
            ['collected_cash' => 0, 'total_earning' => 0, 'total_withdrawn' => 0, 'pending_withdraw' => 0, 'incentive_earning' => 0]
        );

        $wallet->increment('incentive_earning', $bonus->bonus_amount);
        $wallet->increment('total_earning', $bonus->bonus_amount);

        ProvideDMEarning::create([
            'delivery_man_id' => $referrer->id,
            'amount' => $bonus->bonus_amount,
            'ref' => 'referral_bonus_' . $referredDm->id,
            'method' => 'incentive',
            'incentive_type' => 'referral',
            'metadata' => [
                'referred_dm_id' => $referredDm->id,
                'referred_dm_name' => $referredDm->f_name . ' ' . $referredDm->l_name,
            ],
        ]);

        // Notify referrer
        if ($referrer->fcm_token) {
            try {
                Helpers::send_push_notif_to_device($referrer->fcm_token, [
                    'title' => 'Referral Bonus Earned!',
                    'description' => "Your referral {$referredDm->f_name} completed {$minOrders} deliveries. You earned ₹{$bonus->bonus_amount}!",
                    'type' => 'referral_bonus',
                ]);
            } catch (\Exception $e) {
                Log::error("Referral notification failed: " . $e->getMessage());
            }
        }
    }

    public function getReferralStats(DeliveryMan $dm): array
    {
        $this->generateRefCode($dm);

        $referrals = DmReferralBonus::where('referrer_id', $dm->id)->get();

        return [
            'ref_code' => $dm->ref_code,
            'total_referred' => $referrals->count(),
            'bonus_earned' => $referrals->where('status', 'paid')->sum('bonus_amount'),
            'bonus_pending' => $referrals->where('status', 'pending')->sum('bonus_amount'),
            'referrals' => $referrals->map(function ($r) {
                $referred = $r->referred;
                return [
                    'name' => $referred ? ($referred->f_name . ' ' . $referred->l_name) : 'Unknown',
                    'status' => $r->status,
                    'bonus' => $r->bonus_amount,
                    'condition_met' => $r->condition_met,
                    'paid_at' => $r->paid_at?->toDateTimeString(),
                ];
            }),
        ];
    }
}
