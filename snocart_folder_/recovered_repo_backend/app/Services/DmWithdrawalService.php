<?php

namespace App\Services;

use App\Models\DeliveryMan;
use App\Models\DeliveryManWallet;
use App\Models\DmInstantWithdrawal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DmWithdrawalService
{
    const DEFAULT_MIN_AMOUNT = 100;
    const DEFAULT_MAX_DAILY = 3;
    const DEFAULT_FEE = 0;

    public function requestWithdrawal(DeliveryMan $dm, float $amount, string $method, ?array $accountDetails = null): array
    {
        $enabled = DB::table('business_settings')->where('key', 'dm_instant_withdrawal_enabled')->value('value');
        if (!$enabled) {
            return ['success' => false, 'message' => 'Instant withdrawal is not enabled.'];
        }

        $minAmount = (float) (DB::table('business_settings')->where('key', 'dm_min_withdrawal_amount')->value('value') ?: self::DEFAULT_MIN_AMOUNT);
        $maxDaily = (int) (DB::table('business_settings')->where('key', 'dm_max_daily_withdrawals')->value('value') ?: self::DEFAULT_MAX_DAILY);
        $fee = (float) (DB::table('business_settings')->where('key', 'dm_withdrawal_fee')->value('value') ?: self::DEFAULT_FEE);

        if ($amount < $minAmount) {
            return ['success' => false, 'message' => "Minimum withdrawal amount is ₹{$minAmount}."];
        }

        // Check daily limit
        $todayCount = DmInstantWithdrawal::where('delivery_man_id', $dm->id)
            ->whereDate('requested_at', now()->toDateString())
            ->whereIn('status', ['pending', 'processing', 'completed'])
            ->count();

        if ($todayCount >= $maxDaily) {
            return ['success' => false, 'message' => "Maximum {$maxDaily} withdrawals per day."];
        }

        // Check balance
        $wallet = DeliveryManWallet::where('delivery_man_id', $dm->id)->first();
        if (!$wallet) {
            return ['success' => false, 'message' => 'No wallet found.'];
        }

        $available = $wallet->total_earning - $wallet->total_withdrawn - $wallet->pending_withdraw;
        $totalDeduction = $amount + $fee;

        if ($totalDeduction > $available) {
            return ['success' => false, 'message' => "Insufficient balance. Available: ₹" . round($available, 2)];
        }

        $withdrawal = DmInstantWithdrawal::create([
            'delivery_man_id' => $dm->id,
            'amount' => $amount,
            'status' => 'pending',
            'method' => $method,
            'account_details' => $accountDetails,
            'requested_at' => now(),
        ]);

        $wallet->increment('pending_withdraw', $totalDeduction);

        return ['success' => true, 'message' => 'Withdrawal request submitted.', 'withdrawal' => $withdrawal];
    }

    public function processWithdrawal(int $withdrawalId, string $action): array
    {
        $withdrawal = DmInstantWithdrawal::find($withdrawalId);
        if (!$withdrawal || $withdrawal->status !== 'pending') {
            return ['success' => false, 'message' => 'Invalid withdrawal request.'];
        }

        $wallet = DeliveryManWallet::where('delivery_man_id', $withdrawal->delivery_man_id)->first();
        $fee = (float) (DB::table('business_settings')->where('key', 'dm_withdrawal_fee')->value('value') ?: self::DEFAULT_FEE);
        $totalDeduction = $withdrawal->amount + $fee;

        if ($action === 'approve') {
            $withdrawal->update([
                'status' => 'completed',
                'processed_at' => now(),
                'transaction_ref' => 'WD-' . now()->format('YmdHis') . '-' . $withdrawal->id,
            ]);

            $wallet->decrement('pending_withdraw', $totalDeduction);
            $wallet->increment('total_withdrawn', $totalDeduction);

            return ['success' => true, 'message' => 'Withdrawal approved.'];
        }

        if ($action === 'reject') {
            $withdrawal->update(['status' => 'failed', 'processed_at' => now()]);
            $wallet->decrement('pending_withdraw', $totalDeduction);

            return ['success' => true, 'message' => 'Withdrawal rejected, amount returned.'];
        }

        return ['success' => false, 'message' => 'Invalid action.'];
    }

    public function getHistory(DeliveryMan $dm, int $limit = 20): array
    {
        $withdrawals = DmInstantWithdrawal::where('delivery_man_id', $dm->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        $wallet = DeliveryManWallet::where('delivery_man_id', $dm->id)->first();
        $available = $wallet ? ($wallet->total_earning - $wallet->total_withdrawn - $wallet->pending_withdraw) : 0;

        return [
            'available_balance' => round($available, 2),
            'pending_withdrawals' => $withdrawals->where('status', 'pending')->sum('amount'),
            'withdrawals' => $withdrawals->map(function ($w) {
                return [
                    'id' => $w->id,
                    'amount' => $w->amount,
                    'method' => $w->method,
                    'status' => $w->status,
                    'requested_at' => $w->requested_at?->toDateTimeString(),
                    'processed_at' => $w->processed_at?->toDateTimeString(),
                    'transaction_ref' => $w->transaction_ref,
                ];
            }),
        ];
    }
}
