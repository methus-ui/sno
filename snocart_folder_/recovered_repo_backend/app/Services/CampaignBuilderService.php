<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class CampaignBuilderService
{
    protected $segmentationService;

    public function __construct(CustomerSegmentationService $segmentationService)
    {
        $this->segmentationService = $segmentationService;
    }

    /**
     * Create a new WhatsApp campaign
     *
     * @param array $data Campaign configuration
     * @return array Campaign details with campaign_id
     */
    public function createCampaign($data)
    {
        try {
            DB::beginTransaction();

            // Validate campaign data
            $validator = Validator::make($data, [
                'name' => 'required|string|max:255',
                'segment_ids' => 'required|array',
                'segment_ids.*' => 'required|string',
                'message' => 'nullable|string|max:1000',
                'media_url' => 'nullable|url',
                'media_file' => 'nullable|string', // File path
                'scheduled_at' => 'nullable|date|after:now',
                'created_by' => 'required|integer',
            ]);

            if ($validator->fails()) {
                throw new \Exception('Validation failed: ' . implode(', ', $validator->errors()->all()));
            }

            // Get recipients from segments
            $recipients = $this->getRecipients($data['segment_ids']);

            if ($recipients->isEmpty()) {
                throw new \Exception('No recipients found for selected segments');
            }

            // Validate and deduplicate phone numbers
            $validatedRecipients = $this->validateRecipients($recipients->pluck('phone')->toArray());

            if (empty($validatedRecipients)) {
                throw new \Exception('No valid phone numbers found');
            }

            // Calculate cost estimate (₹0.10 per message)
            $costPerMessage = 0.10;
            $estimatedCost = count($validatedRecipients) * $costPerMessage;

            // Create campaign record
            $campaignId = DB::table('wa_campaigns')->insertGetId([
                'name' => $data['name'],
                'segment_ids' => json_encode($data['segment_ids']),
                'message' => $data['message'] ?? null,
                'media_url' => $data['media_url'] ?? null,
                'media_file_path' => $data['media_file'] ?? null,
                'total_recipients' => count($validatedRecipients),
                'status' => isset($data['scheduled_at']) ? 'scheduled' : 'draft',
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'estimated_cost' => $estimatedCost,
                'created_by' => $data['created_by'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Store recipients
            $recipientData = [];
            foreach ($validatedRecipients as $phone) {
                $recipient = $recipients->firstWhere('phone', $phone);
                $recipientData[] = [
                    'campaign_id' => $campaignId,
                    'user_id' => $recipient->id ?? null,
                    'phone' => $phone,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::table('wa_campaign_recipients')->insert($recipientData);

            DB::commit();

            Log::info('WhatsApp campaign created successfully', [
                'campaign_id' => $campaignId,
                'recipient_count' => count($validatedRecipients),
                'estimated_cost' => $estimatedCost,
            ]);

            return [
                'success' => true,
                'campaign_id' => $campaignId,
                'recipient_count' => count($validatedRecipients),
                'estimated_cost' => $estimatedCost,
                'status' => isset($data['scheduled_at']) ? 'scheduled' : 'draft',
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create campaign', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Schedule campaign for future execution
     *
     * @param int $campaignId Campaign ID
     * @param string $scheduledAt Scheduled datetime (Y-m-d H:i:s)
     * @return array Success status
     */
    public function scheduleCampaign($campaignId, $scheduledAt)
    {
        try {
            // Validate scheduled time is in future
            $scheduledTime = Carbon::parse($scheduledAt);
            if ($scheduledTime->isPast()) {
                throw new \Exception('Scheduled time must be in the future');
            }

            // Update campaign
            DB::table('wa_campaigns')
                ->where('id', $campaignId)
                ->update([
                    'scheduled_at' => $scheduledTime,
                    'status' => 'scheduled',
                    'updated_at' => now(),
                ]);

            Log::info('Campaign scheduled successfully', [
                'campaign_id' => $campaignId,
                'scheduled_at' => $scheduledTime->toDateTimeString(),
            ]);

            return [
                'success' => true,
                'scheduled_at' => $scheduledTime->toDateTimeString(),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to schedule campaign', [
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
     * Get all recipients from multiple segments
     *
     * @param array $segmentIds Array of segment identifiers
     * @return \Illuminate\Support\Collection Unique customers with phone numbers
     */
    public function getRecipients($segmentIds)
    {
        try {
            $allCustomers = collect([]);

            foreach ($segmentIds as $segmentId) {
                $customers = $this->segmentationService->getSegmentCustomers($segmentId);
                $allCustomers = $allCustomers->merge($customers);
            }

            // Deduplicate by user ID
            $uniqueCustomers = $allCustomers->unique('id');

            Log::info('Recipients fetched from segments', [
                'segment_count' => count($segmentIds),
                'total_recipients' => $allCustomers->count(),
                'unique_recipients' => $uniqueCustomers->count(),
            ]);

            return $uniqueCustomers;

        } catch (\Exception $e) {
            Log::error('Failed to get recipients', [
                'segment_ids' => $segmentIds,
                'error' => $e->getMessage(),
            ]);
            return collect([]);
        }
    }

    /**
     * Validate and deduplicate phone numbers
     *
     * @param array $phones Array of phone numbers
     * @return array Valid and unique phone numbers
     */
    public function validateRecipients($phones)
    {
        $validPhones = [];
        $invalidCount = 0;

        foreach ($phones as $phone) {
            // Remove all non-numeric characters
            $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

            // Validate 10-digit format
            if (preg_match('/^[6-9]\d{9}$/', $cleanPhone)) {
                $validPhones[] = $cleanPhone;
            } else {
                $invalidCount++;
            }
        }

        // Deduplicate
        $uniquePhones = array_unique($validPhones);

        Log::info('Phone validation completed', [
            'total_phones' => count($phones),
            'valid_phones' => count($validPhones),
            'unique_phones' => count($uniquePhones),
            'invalid_phones' => $invalidCount,
            'duplicates_removed' => count($validPhones) - count($uniquePhones),
        ]);

        return array_values($uniquePhones);
    }

    /**
     * Get campaign statistics
     *
     * @param int $campaignId Campaign ID
     * @return array Campaign stats
     */
    public function getCampaignStats($campaignId)
    {
        try {
            $campaign = DB::table('wa_campaigns')->find($campaignId);

            if (!$campaign) {
                throw new \Exception('Campaign not found');
            }

            $stats = DB::table('wa_campaign_recipients')
                ->where('campaign_id', $campaignId)
                ->select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            return [
                'success' => true,
                'campaign_name' => $campaign->name,
                'total_recipients' => $campaign->total_recipients,
                'status' => $campaign->status,
                'created_at' => $campaign->created_at,
                'scheduled_at' => $campaign->scheduled_at,
                'sent_at' => $campaign->sent_at,
                'completed_at' => $campaign->completed_at,
                'recipient_stats' => [
                    'pending' => $stats['pending'] ?? 0,
                    'sent' => $stats['sent'] ?? 0,
                    'delivered' => $stats['delivered'] ?? 0,
                    'read' => $stats['read'] ?? 0,
                    'failed' => $stats['failed'] ?? 0,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get campaign stats', [
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
     * Cancel scheduled campaign
     *
     * @param int $campaignId Campaign ID
     * @return array Success status
     */
    public function cancelCampaign($campaignId)
    {
        try {
            $campaign = DB::table('wa_campaigns')->find($campaignId);

            if (!$campaign) {
                throw new \Exception('Campaign not found');
            }

            if (!in_array($campaign->status, ['draft', 'scheduled'])) {
                throw new \Exception('Cannot cancel campaign with status: ' . $campaign->status);
            }

            DB::table('wa_campaigns')
                ->where('id', $campaignId)
                ->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'updated_at' => now(),
                ]);

            Log::info('Campaign cancelled', [
                'campaign_id' => $campaignId,
            ]);

            return [
                'success' => true,
                'message' => 'Campaign cancelled successfully',
            ];

        } catch (\Exception $e) {
            Log::error('Failed to cancel campaign', [
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
     * Duplicate existing campaign
     *
     * @param int $campaignId Original campaign ID
     * @param string $newName New campaign name
     * @return array New campaign details
     */
    public function duplicateCampaign($campaignId, $newName)
    {
        try {
            $original = DB::table('wa_campaigns')->find($campaignId);

            if (!$original) {
                throw new \Exception('Original campaign not found');
            }

            $data = [
                'name' => $newName,
                'segment_ids' => json_decode($original->segment_ids, true),
                'message' => $original->message,
                'media_url' => $original->media_url,
                'media_file' => $original->media_file_path,
                'created_by' => $original->created_by,
            ];

            return $this->createCampaign($data);

        } catch (\Exception $e) {
            Log::error('Failed to duplicate campaign', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
