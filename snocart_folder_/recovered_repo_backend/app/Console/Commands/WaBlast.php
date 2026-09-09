<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WaBlast extends Command
{
    protected $signature   = 'wa:blast {campaign_id}';
    protected $description = 'Run a WhatsApp campaign blast in the background';

    // SECURITY FIX: Removed hardcoded credentials - now using config/whatsapp.php
    protected $apiToken;
    protected $phoneNumberId;
    protected $messagesUrl;

    public function __construct()
    {
        parent::__construct();
        $this->apiToken = config('whatsapp.api_token');
        $this->phoneNumberId = config('whatsapp.phone_number_id');
        $apiVersion = config('whatsapp.api_version', 'v19.0');
        $this->messagesUrl = config('whatsapp.api_base_url') . '/' . $apiVersion . '/' . $this->phoneNumberId . '/messages';
    }

    public function handle()
    {
        $campaignId = (int) $this->argument('campaign_id');
        $this->info("Starting wa:blast for campaign #{$campaignId}");

        // Load campaign
        $campaign = DB::selectOne('SELECT * FROM wa_campaigns WHERE id = ?', [$campaignId]);
        if (!$campaign) {
            $this->error("Campaign #{$campaignId} not found");
            return 1;
        }

        // Load phone list
        $phoneFile = storage_path('app/wa_phones_' . $campaignId . '.json');
        if (!file_exists($phoneFile)) {
            $this->error("Phone file not found: {$phoneFile}");
            DB::update("UPDATE wa_campaigns SET status='failed', finished_at=NOW() WHERE id=?", [$campaignId]);
            return 1;
        }
        $phones = json_decode(file_get_contents($phoneFile), true);
        if (empty($phones)) {
            $this->error("Phone list is empty");
            DB::update("UPDATE wa_campaigns SET status='failed', finished_at=NOW() WHERE id=?", [$campaignId]);
            return 1;
        }

        $total         = count($phones);
        $sent          = 0;
        $failed        = 0;
        $failedNumbers = [];
        $statusFile    = storage_path('app/wa_status.json');

        foreach ($phones as $i => $phone) {
            $phone = preg_replace('/[^0-9]/', '', $phone);
            if (strlen($phone) < 7) {
                $failed++;
                $failedNumbers[] = "SKIP:{$phone}";
                continue;
            }

            $payload = json_encode([
                'messaging_product' => 'whatsapp',
                'to'                => $phone,
                'type'              => 'image',
                'image'             => [
                    'id'      => $campaign->image_id,
                    'caption' => $campaign->caption,
                ],
            ]);

            $ch = curl_init($this->messagesUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . $this->apiToken,
                    'Content-Type: application/json',
                ],
                CURLOPT_TIMEOUT        => config('whatsapp.timeouts.request_timeout', 15),
                // SECURITY FIX: SSL verification enabled
                CURLOPT_SSL_VERIFYPEER => config('whatsapp.ssl.verify_peer', true),
                CURLOPT_SSL_VERIFYHOST => config('whatsapp.ssl.verify_host', 2),
            ]);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                Log::error('WhatsApp message send failed', ['phone' => $phone, 'curl_error' => $curlError]);
            }

            $body = json_decode($resp, true);
            if ($code === 200 && isset($body['messages'][0]['id'])) {
                $sent++;
            } else {
                $failed++;
                $errMsg = $body['error']['message'] ?? 'HTTP ' . $code;
                $failedNumbers[] = "{$phone}: {$errMsg}";
                $this->warn("Failed {$phone}: {$errMsg}");
            }

            // Update status file every 10 messages
            if (($i + 1) % 10 === 0 || ($i + 1) === $total) {
                $statusData = [
                    'status'         => 'running',
                    'campaign_id'    => $campaignId,
                    'campaign_name'  => $campaign->name,
                    'total'          => $total,
                    'sent'           => $sent,
                    'failed'         => $failed,
                    'failed_numbers' => $failedNumbers,
                    'started_at'     => $campaign->started_at,
                ];
                file_put_contents($statusFile, json_encode($statusData));
                // Also update DB counts
                DB::update('UPDATE wa_campaigns SET sent=?, failed=? WHERE id=?', [$sent, $failed, $campaignId]);
            }

            // 200ms delay between messages
            usleep(200000);
        }

        // Mark complete
        DB::update("UPDATE wa_campaigns SET sent=?, failed=?, status='completed', finished_at=NOW() WHERE id=?",
            [$sent, $failed, $campaignId]);

        $finalStatus = [
            'status'         => 'completed',
            'campaign_id'    => $campaignId,
            'campaign_name'  => $campaign->name,
            'total'          => $total,
            'sent'           => $sent,
            'failed'         => $failed,
            'failed_numbers' => $failedNumbers,
            'finished_at'    => date('Y-m-d H:i:s'),
        ];
        file_put_contents($statusFile, json_encode($finalStatus));

        $this->info("Campaign #{$campaignId} completed: {$sent} sent, {$failed} failed of {$total}");
        return 0;
    }
}
