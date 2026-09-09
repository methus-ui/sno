<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CustomerSegmentationService
{
    /**
     * Predefined segment definitions
     */
    protected $predefinedSegments = [
        'inactive_7d' => [
            'name' => 'Inactive (7 Days)',
            'description' => 'Customers who haven\'t ordered in 7 days',
            'query_method' => 'getInactiveCustomers',
            'params' => ['days' => 7],
        ],
        'inactive_15d' => [
            'name' => 'Inactive (15 Days)',
            'description' => 'Customers who haven\'t ordered in 15 days',
            'query_method' => 'getInactiveCustomers',
            'params' => ['days' => 15],
        ],
        'inactive_30d' => [
            'name' => 'Inactive (30 Days)',
            'description' => 'Customers who haven\'t ordered in 30 days',
            'query_method' => 'getInactiveCustomers',
            'params' => ['days' => 30],
        ],
        'inactive_60d' => [
            'name' => 'Inactive (60+ Days)',
            'description' => 'Customers who haven\'t ordered in 60+ days',
            'query_method' => 'getInactiveCustomers',
            'params' => ['days' => 60],
        ],
        'vip' => [
            'name' => 'VIP Customers',
            'description' => 'Customers who spent >₹10,000 lifetime',
            'query_method' => 'getVIPCustomers',
            'params' => ['min_spent' => 10000],
        ],
        'high_spenders' => [
            'name' => 'High Spenders',
            'description' => 'Customers with >₹5,000 lifetime value',
            'query_method' => 'getHighSpenders',
            'params' => ['min_spent' => 5000],
        ],
        'at_risk' => [
            'name' => 'At-Risk Customers',
            'description' => 'Previously active (3+ orders) but inactive 14+ days',
            'query_method' => 'getAtRiskCustomers',
            'params' => ['min_orders' => 3, 'days_inactive' => 14],
        ],
        'frequent_buyers' => [
            'name' => 'Frequent Buyers',
            'description' => 'Customers with 5+ orders in last 30 days',
            'query_method' => 'getFrequentBuyers',
            'params' => ['min_orders' => 5, 'days' => 30],
        ],
        'one_time_buyers' => [
            'name' => 'One-Time Buyers',
            'description' => 'Customers with exactly 1 order ever',
            'query_method' => 'getOneTimeBuyers',
            'params' => [],
        ],
        'new_customers' => [
            'name' => 'New Customers',
            'description' => 'Customers who joined in last 7 days',
            'query_method' => 'getNewCustomers',
            'params' => ['days' => 7],
        ],
        'milestone_5th' => [
            'name' => '5th Order Milestone',
            'description' => 'Customers who just placed their 5th order',
            'query_method' => 'getMilestoneCustomers',
            'params' => ['order_count' => 5],
        ],
        'milestone_10th' => [
            'name' => '10th Order Milestone',
            'description' => 'Customers who just placed their 10th order',
            'query_method' => 'getMilestoneCustomers',
            'params' => ['order_count' => 10],
        ],
        'milestone_50th' => [
            'name' => '50th Order Milestone',
            'description' => 'Customers who just placed their 50th order',
            'query_method' => 'getMilestoneCustomers',
            'params' => ['order_count' => 50],
        ],
        'cart_abandoners' => [
            'name' => 'Cart Abandoners',
            'description' => 'Customers with items in cart but no order in 24h',
            'query_method' => 'getCartAbandoners',
            'params' => ['hours' => 24],
        ],
        'first_time_discount_users' => [
            'name' => 'First-Time Discount Users',
            'description' => 'Customers who placed first order with discount',
            'query_method' => 'getFirstTimeDiscountUsers',
            'params' => [],
        ],
        'birthday_customers' => [
            'name' => 'Birthday This Month',
            'description' => 'Customers with birthdays in current month',
            'query_method' => 'getBirthdayCustomers',
            'params' => [],
        ],
    ];

    /**
     * Get customers for a specific segment
     *
     * @param string $segmentId Predefined segment ID or custom segment ID
     * @return \Illuminate\Support\Collection Customer records with phone numbers
     */
    public function getSegmentCustomers($segmentId)
    {
        try {
            // Check if it's a predefined segment
            if (isset($this->predefinedSegments[$segmentId])) {
                $segment = $this->predefinedSegments[$segmentId];
                $method = $segment['query_method'];
                $params = $segment['params'];

                // Call the corresponding query method
                return $this->$method($params);
            }

            // Custom segment (from wa_segments table)
            $customSegment = DB::table('wa_segments')->find($segmentId);
            if (!$customSegment) {
                throw new \Exception('Segment not found: ' . $segmentId);
            }

            // Build query from saved filters
            $filters = json_decode($customSegment->filters, true);
            return $this->buildCustomQuery($filters);

        } catch (\Exception $e) {
            Log::error('Failed to get segment customers', [
                'segment_id' => $segmentId,
                'error' => $e->getMessage(),
            ]);
            return collect([]);
        }
    }

    /**
     * Refresh segment customer cache
     *
     * @param string $segmentId Segment identifier
     * @return int Number of customers cached
     */
    public function refreshSegmentCache($segmentId)
    {
        try {
            $customers = $this->getSegmentCustomers($segmentId);

            // Store in wa_segment_customers table
            DB::table('wa_segment_customers')->where('segment_id', $segmentId)->delete();

            $insertData = $customers->map(function ($customer) use ($segmentId) {
                return [
                    'segment_id' => $segmentId,
                    'user_id' => $customer->id,
                    'phone' => $customer->phone,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            if (!empty($insertData)) {
                DB::table('wa_segment_customers')->insert($insertData);
            }

            // Update cache metadata
            $cacheKey = 'segment_cache:' . $segmentId;
            Cache::put($cacheKey, [
                'count' => count($insertData),
                'cached_at' => now()->toDateTimeString(),
            ], 3600); // 1 hour TTL

            Log::info('Segment cache refreshed', [
                'segment_id' => $segmentId,
                'customer_count' => count($insertData),
            ]);

            return count($insertData);

        } catch (\Exception $e) {
            Log::error('Failed to refresh segment cache', [
                'segment_id' => $segmentId,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Create custom segment with filters
     *
     * @param string $name Segment name
     * @param array $filters Filter conditions
     * @return array Segment details
     */
    public function createCustomSegment($name, $filters)
    {
        try {
            DB::beginTransaction();

            $segmentId = DB::table('wa_segments')->insertGetId([
                'name' => $name,
                'filters' => json_encode($filters),
                'type' => 'custom',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Refresh cache for new segment
            $count = $this->refreshSegmentCache($segmentId);

            DB::commit();

            return [
                'success' => true,
                'segment_id' => $segmentId,
                'customer_count' => $count,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create custom segment', [
                'name' => $name,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get list of all predefined segments
     *
     * @return array Segment definitions
     */
    public function getPredefinedSegments()
    {
        return $this->predefinedSegments;
    }

    // ==================== Query Methods ====================

    protected function getInactiveCustomers($params)
    {
        $days = $params['days'];
        $cutoffDate = now()->subDays($days);

        return User::whereHas('orders', function ($query) {
            $query->whereIn('order_status', ['delivered', 'refunded']);
        })
        ->whereDoesntHave('orders', function ($query) use ($cutoffDate) {
            $query->where('created_at', '>=', $cutoffDate);
        })
        ->whereNotNull('phone')
        ->select('id', 'f_name', 'l_name', 'phone', 'email')
        ->get();
    }

    protected function getVIPCustomers($params)
    {
        $minSpent = $params['min_spent'];

        return User::whereHas('orders', function ($query) use ($minSpent) {
            $query->whereIn('order_status', ['delivered', 'refunded'])
                  ->havingRaw('SUM(order_amount) >= ?', [$minSpent]);
        })
        ->whereNotNull('phone')
        ->select('id', 'f_name', 'l_name', 'phone', 'email')
        ->get();
    }

    protected function getHighSpenders($params)
    {
        $minSpent = $params['min_spent'];

        return User::select('users.id', 'users.f_name', 'users.l_name', 'users.phone', 'users.email')
            ->join('orders', 'users.id', '=', 'orders.user_id')
            ->whereIn('orders.order_status', ['delivered', 'refunded'])
            ->groupBy('users.id', 'users.f_name', 'users.l_name', 'users.phone', 'users.email')
            ->havingRaw('SUM(orders.order_amount) >= ?', [$minSpent])
            ->whereNotNull('users.phone')
            ->get();
    }

    protected function getAtRiskCustomers($params)
    {
        $minOrders = $params['min_orders'];
        $daysInactive = $params['days_inactive'];
        $cutoffDate = now()->subDays($daysInactive);

        return User::select('users.id', 'users.f_name', 'users.l_name', 'users.phone', 'users.email')
            ->join('orders', 'users.id', '=', 'orders.user_id')
            ->whereIn('orders.order_status', ['delivered', 'refunded'])
            ->groupBy('users.id', 'users.f_name', 'users.l_name', 'users.phone', 'users.email')
            ->havingRaw('COUNT(orders.id) >= ?', [$minOrders])
            ->havingRaw('MAX(orders.created_at) < ?', [$cutoffDate])
            ->whereNotNull('users.phone')
            ->get();
    }

    protected function getFrequentBuyers($params)
    {
        $minOrders = $params['min_orders'];
        $days = $params['days'];
        $startDate = now()->subDays($days);

        return User::select('users.id', 'users.f_name', 'users.l_name', 'users.phone', 'users.email')
            ->join('orders', 'users.id', '=', 'orders.user_id')
            ->where('orders.created_at', '>=', $startDate)
            ->whereIn('orders.order_status', ['delivered', 'refunded'])
            ->groupBy('users.id', 'users.f_name', 'users.l_name', 'users.phone', 'users.email')
            ->havingRaw('COUNT(orders.id) >= ?', [$minOrders])
            ->whereNotNull('users.phone')
            ->get();
    }

    protected function getOneTimeBuyers($params)
    {
        return User::select('users.id', 'users.f_name', 'users.l_name', 'users.phone', 'users.email')
            ->join('orders', 'users.id', '=', 'orders.user_id')
            ->whereIn('orders.order_status', ['delivered', 'refunded'])
            ->groupBy('users.id', 'users.f_name', 'users.l_name', 'users.phone', 'users.email')
            ->havingRaw('COUNT(orders.id) = 1')
            ->whereNotNull('users.phone')
            ->get();
    }

    protected function getNewCustomers($params)
    {
        $days = $params['days'];
        $cutoffDate = now()->subDays($days);

        return User::where('created_at', '>=', $cutoffDate)
            ->whereNotNull('phone')
            ->select('id', 'f_name', 'l_name', 'phone', 'email')
            ->get();
    }

    protected function getMilestoneCustomers($params)
    {
        $orderCount = $params['order_count'];

        return User::select('users.id', 'users.f_name', 'users.l_name', 'users.phone', 'users.email')
            ->join('orders', 'users.id', '=', 'orders.user_id')
            ->whereIn('orders.order_status', ['delivered', 'refunded'])
            ->groupBy('users.id', 'users.f_name', 'users.l_name', 'users.phone', 'users.email')
            ->havingRaw('COUNT(orders.id) = ?', [$orderCount])
            ->whereNotNull('users.phone')
            ->get();
    }

    protected function getCartAbandoners($params)
    {
        $hours = $params['hours'];
        $cutoffTime = now()->subHours($hours);

        return User::select('users.id', 'users.f_name', 'users.l_name', 'users.phone', 'users.email')
            ->join('cart_items', 'users.id', '=', 'cart_items.user_id')
            ->whereDoesntHave('orders', function ($query) use ($cutoffTime) {
                $query->where('created_at', '>=', $cutoffTime);
            })
            ->groupBy('users.id', 'users.f_name', 'users.l_name', 'users.phone', 'users.email')
            ->having(DB::raw('COUNT(cart_items.id)'), '>', 0)
            ->whereNotNull('users.phone')
            ->get();
    }

    protected function getFirstTimeDiscountUsers($params)
    {
        return User::select('users.id', 'users.f_name', 'users.l_name', 'users.phone', 'users.email')
            ->join('orders', 'users.id', '=', 'orders.user_id')
            ->whereIn('orders.order_status', ['delivered', 'refunded'])
            ->where('orders.coupon_discount_amount', '>', 0)
            ->groupBy('users.id', 'users.f_name', 'users.l_name', 'users.phone', 'users.email')
            ->havingRaw('COUNT(orders.id) = 1')
            ->whereNotNull('users.phone')
            ->get();
    }

    protected function getBirthdayCustomers($params)
    {
        $currentMonth = now()->month;

        return User::whereNotNull('phone')
            ->whereRaw('MONTH(date_of_birth) = ?', [$currentMonth])
            ->select('id', 'f_name', 'l_name', 'phone', 'email', 'date_of_birth')
            ->get();
    }

    /**
     * Build custom query from filter array
     *
     * @param array $filters Filter conditions
     * @return \Illuminate\Support\Collection
     */
    protected function buildCustomQuery($filters)
    {
        $query = User::query()->whereNotNull('phone');

        foreach ($filters as $filter) {
            $field = $filter['field'] ?? null;
            $operator = $filter['operator'] ?? '=';
            $value = $filter['value'] ?? null;

            if ($field && $value !== null) {
                $query->where($field, $operator, $value);
            }
        }

        return $query->select('id', 'f_name', 'l_name', 'phone', 'email')->get();
    }
}
