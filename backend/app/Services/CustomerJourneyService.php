<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CustomerJourneyService
{
    /**
     * Valid event types for tracking
     */
    protected $validEventTypes = [
        'campaign_sent',
        'message_delivered',
        'message_read',
        'order_placed',
        'app_opened',
        'cart_updated',
        'payment_completed',
        'order_cancelled',
    ];

    /**
     * Record customer journey event
     *
     * @param int $userId User ID
     * @param int $campaignId Campaign ID
     * @param string $eventType Event type (campaign_sent, message_delivered, etc.)
     * @param array $data Additional event data
     * @return array Success status
     */
    public function recordEvent($userId, $campaignId, $eventType, $data = [])
    {
        try {
            // Validate event type
            if (!in_array($eventType, $this->validEventTypes)) {
                throw new \Exception('Invalid event type: ' . $eventType);
            }

            // Validate user and campaign exist
            $userExists = DB::table('users')->where('id', $userId)->exists();
            if (!$userExists) {
                throw new \Exception('User not found: ' . $userId);
            }

            $campaignExists = DB::table('wa_campaigns')->where('id', $campaignId)->exists();
            if (!$campaignExists) {
                throw new \Exception('Campaign not found: ' . $campaignId);
            }

            // Record event
            $eventId = DB::table('wa_customer_journey')->insertGetId([
                'user_id' => $userId,
                'campaign_id' => $campaignId,
                'event_type' => $eventType,
                'event_data' => json_encode($data),
                'event_timestamp' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update campaign recipient status based on event type
            $this->updateRecipientStatus($userId, $campaignId, $eventType, $data);

            Log::info('Customer journey event recorded', [
                'event_id' => $eventId,
                'user_id' => $userId,
                'campaign_id' => $campaignId,
                'event_type' => $eventType,
            ]);

            return [
                'success' => true,
                'event_id' => $eventId,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to record customer journey event', [
                'user_id' => $userId,
                'campaign_id' => $campaignId,
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get complete customer journey for a campaign
     *
     * @param int $userId User ID
     * @param int $campaignId Campaign ID
     * @return array Journey events in chronological order
     */
    public function getJourney($userId, $campaignId)
    {
        try {
            $events = DB::table('wa_customer_journey')
                ->where('user_id', $userId)
                ->where('campaign_id', $campaignId)
                ->orderBy('event_timestamp', 'asc')
                ->get();

            $timeline = $events->map(function ($event) {
                return [
                    'event_type' => $event->event_type,
                    'timestamp' => $event->event_timestamp,
                    'data' => json_decode($event->event_data, true),
                ];
            })->toArray();

            // Calculate journey metrics
            $metrics = $this->calculateJourneyMetrics($events);

            return [
                'success' => true,
                'user_id' => $userId,
                'campaign_id' => $campaignId,
                'timeline' => $timeline,
                'metrics' => $metrics,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get customer journey', [
                'user_id' => $userId,
                'campaign_id' => $campaignId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Analyze conversion paths for campaign
     *
     * @param int $campaignId Campaign ID
     * @return array Conversion path analysis
     */
    public function analyzeConversionPath($campaignId)
    {
        try {
            // Get all customers who converted (placed order)
            $converters = DB::table('wa_customer_journey as wcj')
                ->select('wcj.user_id')
                ->where('wcj.campaign_id', $campaignId)
                ->where('wcj.event_type', 'order_placed')
                ->distinct()
                ->pluck('user_id');

            // Analyze journey patterns for converters
            $pathAnalysis = [];

            foreach ($converters as $userId) {
                $journey = DB::table('wa_customer_journey')
                    ->where('user_id', $userId)
                    ->where('campaign_id', $campaignId)
                    ->orderBy('event_timestamp', 'asc')
                    ->get();

                // Create path sequence
                $path = $journey->pluck('event_type')->toArray();
                $pathKey = implode(' → ', $path);

                if (!isset($pathAnalysis[$pathKey])) {
                    $pathAnalysis[$pathKey] = [
                        'path' => $path,
                        'count' => 0,
                        'avg_time_to_conversion' => 0,
                        'conversion_times' => [],
                    ];
                }

                $pathAnalysis[$pathKey]['count']++;

                // Calculate time to conversion
                $firstEvent = $journey->first();
                $lastEvent = $journey->last();

                if ($firstEvent && $lastEvent) {
                    $timeToConversion = Carbon::parse($firstEvent->event_timestamp)
                        ->diffInMinutes(Carbon::parse($lastEvent->event_timestamp));
                    $pathAnalysis[$pathKey]['conversion_times'][] = $timeToConversion;
                }
            }

            // Calculate average conversion time for each path
            foreach ($pathAnalysis as $key => &$analysis) {
                if (!empty($analysis['conversion_times'])) {
                    $analysis['avg_time_to_conversion'] = round(
                        array_sum($analysis['conversion_times']) / count($analysis['conversion_times']),
                        2
                    );
                }
                unset($analysis['conversion_times']); // Remove raw data
            }

            // Sort paths by count (most common first)
            uasort($pathAnalysis, function ($a, $b) {
                return $b['count'] <=> $a['count'];
            });

            // Calculate overall metrics
            $totalConverters = count($converters);
            $totalRecipients = DB::table('wa_campaign_recipients')
                ->where('campaign_id', $campaignId)
                ->count();

            $overallConversionRate = $totalRecipients > 0
                ? round(($totalConverters / $totalRecipients) * 100, 2)
                : 0;

            return [
                'success' => true,
                'campaign_id' => $campaignId,
                'total_recipients' => $totalRecipients,
                'total_converters' => $totalConverters,
                'overall_conversion_rate' => $overallConversionRate,
                'conversion_paths' => array_values($pathAnalysis),
                'unique_paths' => count($pathAnalysis),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to analyze conversion path', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get aggregate journey statistics for campaign
     *
     * @param int $campaignId Campaign ID
     * @return array Aggregate statistics
     */
    public function getCampaignJourneyStats($campaignId)
    {
        try {
            // Count events by type
            $eventCounts = DB::table('wa_customer_journey')
                ->where('campaign_id', $campaignId)
                ->select('event_type', DB::raw('COUNT(*) as count'))
                ->groupBy('event_type')
                ->pluck('count', 'event_type')
                ->toArray();

            // Calculate drop-off rates
            $totalSent = $eventCounts['campaign_sent'] ?? 0;
            $delivered = $eventCounts['message_delivered'] ?? 0;
            $read = $eventCounts['message_read'] ?? 0;
            $appOpened = $eventCounts['app_opened'] ?? 0;
            $orderPlaced = $eventCounts['order_placed'] ?? 0;

            $dropoffRates = [
                'sent_to_delivered' => $totalSent > 0 ? round((1 - $delivered / $totalSent) * 100, 2) : 0,
                'delivered_to_read' => $delivered > 0 ? round((1 - $read / $delivered) * 100, 2) : 0,
                'read_to_app_opened' => $read > 0 ? round((1 - $appOpened / $read) * 100, 2) : 0,
                'app_opened_to_order' => $appOpened > 0 ? round((1 - $orderPlaced / $appOpened) * 100, 2) : 0,
            ];

            // Calculate average time between events
            $avgTimeBetweenEvents = $this->calculateAvgTimeBetweenEvents($campaignId);

            return [
                'success' => true,
                'campaign_id' => $campaignId,
                'event_counts' => $eventCounts,
                'dropoff_rates' => $dropoffRates,
                'avg_time_between_events' => $avgTimeBetweenEvents,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get campaign journey stats', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update recipient status based on journey event
     *
     * @param int $userId User ID
     * @param int $campaignId Campaign ID
     * @param string $eventType Event type
     * @param array $data Event data
     */
    protected function updateRecipientStatus($userId, $campaignId, $eventType, $data)
    {
        try {
            $statusMapping = [
                'campaign_sent' => 'sent',
                'message_delivered' => 'delivered',
                'message_read' => 'read',
            ];

            if (isset($statusMapping[$eventType])) {
                $updateData = [
                    'status' => $statusMapping[$eventType],
                    'updated_at' => now(),
                ];

                // Add timestamp fields
                if ($eventType === 'message_delivered') {
                    $updateData['delivered_at'] = now();
                } elseif ($eventType === 'message_read') {
                    $updateData['read_at'] = now();
                }

                // Add WhatsApp message ID if available
                if (isset($data['message_id'])) {
                    $updateData['whatsapp_message_id'] = $data['message_id'];
                }

                DB::table('wa_campaign_recipients')
                    ->where('campaign_id', $campaignId)
                    ->where('user_id', $userId)
                    ->update($updateData);
            }

        } catch (\Exception $e) {
            Log::error('Failed to update recipient status', [
                'user_id' => $userId,
                'campaign_id' => $campaignId,
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Calculate journey metrics from events
     *
     * @param \Illuminate\Support\Collection $events Journey events
     * @return array Metrics
     */
    protected function calculateJourneyMetrics($events)
    {
        $metrics = [
            'total_events' => $events->count(),
            'unique_event_types' => $events->pluck('event_type')->unique()->count(),
            'first_event' => null,
            'last_event' => null,
            'journey_duration_minutes' => 0,
        ];

        if ($events->isNotEmpty()) {
            $first = $events->first();
            $last = $events->last();

            $metrics['first_event'] = [
                'type' => $first->event_type,
                'timestamp' => $first->event_timestamp,
            ];

            $metrics['last_event'] = [
                'type' => $last->event_type,
                'timestamp' => $last->event_timestamp,
            ];

            $metrics['journey_duration_minutes'] = Carbon::parse($first->event_timestamp)
                ->diffInMinutes(Carbon::parse($last->event_timestamp));
        }

        return $metrics;
    }

    /**
     * Calculate average time between consecutive events
     *
     * @param int $campaignId Campaign ID
     * @return array Average times
     */
    protected function calculateAvgTimeBetweenEvents($campaignId)
    {
        try {
            $avgTimes = [];

            $eventPairs = [
                ['campaign_sent', 'message_delivered'],
                ['message_delivered', 'message_read'],
                ['message_read', 'app_opened'],
                ['app_opened', 'order_placed'],
            ];

            foreach ($eventPairs as $pair) {
                $times = DB::table('wa_customer_journey as wcj1')
                    ->join('wa_customer_journey as wcj2', function ($join) use ($pair) {
                        $join->on('wcj1.user_id', '=', 'wcj2.user_id')
                             ->on('wcj1.campaign_id', '=', 'wcj2.campaign_id');
                    })
                    ->where('wcj1.campaign_id', $campaignId)
                    ->where('wcj1.event_type', $pair[0])
                    ->where('wcj2.event_type', $pair[1])
                    ->whereRaw('wcj2.event_timestamp > wcj1.event_timestamp')
                    ->selectRaw('TIMESTAMPDIFF(MINUTE, wcj1.event_timestamp, wcj2.event_timestamp) as diff')
                    ->pluck('diff');

                if ($times->isNotEmpty()) {
                    $avgTimes[$pair[0] . '_to_' . $pair[1]] = round($times->avg(), 2);
                } else {
                    $avgTimes[$pair[0] . '_to_' . $pair[1]] = null;
                }
            }

            return $avgTimes;

        } catch (\Exception $e) {
            Log::error('Failed to calculate avg time between events', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}
