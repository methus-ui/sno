<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CalculateSnoscore extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'snoscore:calculate
                            {--user= : Calculate for specific user ID}
                            {--reset : Reset all counts before calculating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate and backfill Snoscore (customer behavior rating) for all users based on their order history';

    /**
     * Customer-initiated cancellation identifiers
     */
    private array $customerTypes = ['customer', 'user', 'client'];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting Snoscore calculation...');
        $this->newLine();

        $userId = $this->option('user');
        $reset = $this->option('reset');

        // Get users to process
        $query = User::query();
        if ($userId) {
            $query->where('id', $userId);
        }

        $users = $query->get();
        $total = $users->count();

        if ($total === 0) {
            $this->warn('No users found to process.');
            return 0;
        }

        $this->info("Processing {$total} users...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $processed = 0;
        $updated = 0;

        foreach ($users as $user) {
            $result = $this->calculateUserSnoscore($user, $reset);
            if ($result) {
                $updated++;
            }
            $processed++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Completed! Processed {$processed} users, updated {$updated} snoscores.");
        $this->newLine();

        // Show summary statistics
        $this->showSummaryStats();

        return 0;
    }

    /**
     * Calculate snoscore for a single user
     */
    private function calculateUserSnoscore(User $user, bool $reset = false): bool
    {
        // Get all orders for this user (non-guest)
        $orders = Order::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('is_guest', 0)
            ->get();

        $totalOrders = $orders->count();

        if ($totalOrders === 0) {
            // No orders, set default score
            $user->update([
                'customer_canceled_count' => 0,
                'other_canceled_count' => 0,
                'refund_count' => 0,
                'snoscore' => 5.00,
                'snoscore_status' => 'excellent'
            ]);
            return true;
        }

        // Count cancellations and refunds
        $customerCancels = 0;
        $otherCancels = 0;
        $refundCount = 0;

        foreach ($orders as $order) {
            if ($order->order_status === 'canceled') {
                $canceledBy = strtolower($order->canceled_by ?? '');

                if ($this->wasCustomerInitiated($canceledBy)) {
                    $customerCancels++;
                } else {
                    $otherCancels++;
                }
            }

            if ($order->order_status === 'refunded') {
                $refundCount++;
            }
        }

        // Calculate weighted snoscore
        $weightedCancels = ($customerCancels * 1.0) + ($otherCancels * 0.3);
        $cancelRate = $weightedCancels / $totalOrders;
        $snoscore = round(max(1.0, min(5.0, 5 - ($cancelRate * 4))), 2);

        // Determine status
        $status = match(true) {
            $snoscore >= 4.5 => 'excellent',
            $snoscore >= 3.5 => 'good',
            $snoscore >= 2.5 => 'fair',
            $snoscore >= 1.5 => 'poor',
            default => 'risky',
        };

        // Update user
        $user->update([
            'customer_canceled_count' => $customerCancels,
            'other_canceled_count' => $otherCancels,
            'refund_count' => $refundCount,
            'snoscore' => $snoscore,
            'snoscore_status' => $status
        ]);

        return true;
    }

    /**
     * Determine if cancellation was customer-initiated
     */
    private function wasCustomerInitiated(string $canceledBy): bool
    {
        foreach ($this->customerTypes as $type) {
            if (str_contains($canceledBy, $type)) {
                return true;
            }
        }

        // Empty or unknown defaults to customer-initiated
        if (empty($canceledBy)) {
            return true;
        }

        return false;
    }

    /**
     * Show summary statistics after processing
     */
    private function showSummaryStats(): void
    {
        $this->info('=== Snoscore Distribution ===');

        $stats = User::selectRaw('snoscore_status, COUNT(*) as count')
            ->groupBy('snoscore_status')
            ->get()
            ->pluck('count', 'snoscore_status')
            ->toArray();

        $statusOrder = ['excellent', 'good', 'fair', 'poor', 'risky'];
        $colors = [
            'excellent' => 'green',
            'good' => 'blue',
            'fair' => 'yellow',
            'poor' => 'magenta',
            'risky' => 'red'
        ];

        foreach ($statusOrder as $status) {
            $count = $stats[$status] ?? 0;
            $color = $colors[$status] ?? 'white';
            $this->line("<fg={$color}>" . ucfirst($status) . ": {$count} users</>");
        }

        $this->newLine();

        // Show risky customers if any
        $riskyUsers = User::where('snoscore', '<', 2.5)
            ->orderBy('snoscore')
            ->limit(10)
            ->get(['id', 'f_name', 'l_name', 'phone', 'snoscore', 'customer_canceled_count', 'other_canceled_count']);

        if ($riskyUsers->count() > 0) {
            $this->warn('=== Top Risky Customers (Snoscore < 2.5) ===');
            $headers = ['ID', 'Name', 'Phone', 'Snoscore', 'Customer Cancels', 'Other Cancels'];
            $rows = $riskyUsers->map(function ($user) {
                return [
                    $user->id,
                    $user->f_name . ' ' . $user->l_name,
                    $user->phone ?? 'N/A',
                    number_format($user->snoscore, 2),
                    $user->customer_canceled_count,
                    $user->other_canceled_count
                ];
            })->toArray();

            $this->table($headers, $rows);
        }
    }
}
