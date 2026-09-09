<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendWhatsAppCampaignJob;

class WhatsAppController extends Controller
{
    // SECURITY FIX: Removed hardcoded credentials - now using config/whatsapp.php
    protected $apiToken;
    protected $phoneNumberId;
    protected $apiBaseUrl;

    public function __construct()
    {
        $this->apiToken = config('whatsapp.api_token');
        $this->phoneNumberId = config('whatsapp.phone_number_id');
        $this->apiBaseUrl = config('whatsapp.api_base_url') . '/' . config('whatsapp.api_version');
    }

    public function index()
    {
        // Get WA account status
        $accountStatus = null;
        $accountError  = null;

        $ch = curl_init($this->apiBaseUrl . '/' . $this->phoneNumberId . '?fields=display_phone_number,verified_name,quality_rating,account_mode');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $this->apiToken],
            CURLOPT_TIMEOUT        => config('whatsapp.timeouts.request_timeout', 10),
            // SECURITY FIX: SSL verification enabled
            CURLOPT_SSL_VERIFYPEER => config('whatsapp.ssl.verify_peer', true),
            CURLOPT_SSL_VERIFYHOST => config('whatsapp.ssl.verify_host', 2),
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200) {
            $body = json_decode($resp, true);
            if (isset($body['display_phone_number'])) {
                $accountStatus = $body;
            } else {
                $accountError = $body['error']['message'] ?? 'Unknown API error';
            }
        } else {
            $decoded = json_decode($resp, true);
            $accountError = $decoded['error']['message'] ?? 'Failed to connect to WhatsApp API (HTTP ' . $code . ')';
        }

        // Campaign history
        $campaigns = DB::select('SELECT * FROM wa_campaigns ORDER BY started_at DESC LIMIT 20');

        // Current status
        $statusFile = storage_path('app/wa_status.json');
        $statusData = null;
        if (file_exists($statusFile)) {
            $statusData = json_decode(file_get_contents($statusFile), true);
        }

        // Audience counts
        $inactiveCount = DB::selectOne("SELECT COUNT(DISTINCT u.id) AS cnt FROM users u
            WHERE u.phone IS NOT NULL AND u.phone != '' AND u.status = 1
            AND u.id IN (SELECT user_id FROM orders WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY))
            AND u.id NOT IN (SELECT user_id FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY))")->cnt ?? 0;

        $allCustomerCount = DB::selectOne("SELECT COUNT(DISTINCT u.id) AS cnt FROM users u
            WHERE u.phone IS NOT NULL AND u.phone != '' AND u.status = 1
            AND u.id IN (SELECT DISTINCT user_id FROM orders WHERE user_id IS NOT NULL)")->cnt ?? 0;

        return view('admin-views.whatsapp.index', compact(
            'accountStatus', 'accountError', 'campaigns', 'statusData', 'inactiveCount', 'allCustomerCount'
        ));
    }

    public function uploadMedia(Request $request)
    {
        try {
            if ($request->input('type') === 'url') {
                // Download from URL then upload
                $url = $request->input('url');
                if (!$url) {
                    return response()->json(['success' => false, 'error' => 'No URL provided']);
                }
                $imgData = @file_get_contents($url);
                if (!$imgData) {
                    return response()->json(['success' => false, 'error' => 'Could not download image from URL']);
                }
                $tmpPath = tempnam(sys_get_temp_dir(), 'wa_img_') . '.jpg';
                file_put_contents($tmpPath, $imgData);
                $mediaId = $this->uploadFileToBusiness($tmpPath, 'image/jpeg');
                @unlink($tmpPath);
            } else {
                // File upload
                if (!$request->hasFile('image')) {
                    return response()->json(['success' => false, 'error' => 'No file uploaded']);
                }
                $file    = $request->file('image');
                $tmpPath = $file->getPathname();
                $mime    = $file->getMimeType() ?: 'image/jpeg';
                $mediaId = $this->uploadFileToBusiness($tmpPath, $mime);
            }

            if ($mediaId) {
                return response()->json(['success' => true, 'media_id' => $mediaId]);
            }
            return response()->json(['success' => false, 'error' => 'Upload failed']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function uploadFileToBusiness(string $filePath, string $mime): ?string
    {
        $url = $this->apiBaseUrl . '/' . $this->phoneNumberId . '/media';

        $ch = curl_init($url);
        $cfile = new \CURLFile($filePath, $mime, basename($filePath));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => ['messaging_product' => 'whatsapp', 'file' => $cfile],
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $this->apiToken],
            CURLOPT_TIMEOUT        => 60,
            // SECURITY FIX: SSL verification enabled
            CURLOPT_SSL_VERIFYPEER => config('whatsapp.ssl.verify_peer', true),
            CURLOPT_SSL_VERIFYHOST => config('whatsapp.ssl.verify_host', 2),
        ]);
        $resp = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            Log::error('WhatsApp media upload failed', ['error' => $curlError]);
            return null;
        }

        $body = json_decode($resp, true);
        return $body['id'] ?? null;
    }

    public function sendBlast(Request $request)
    {
        try {
            // SECURITY FIX: Add pessimistic locking to prevent race conditions
            DB::beginTransaction();

            $name     = trim($request->input('name', ''));
            $mediaId  = trim($request->input('media_id', ''));
            $caption  = trim($request->input('caption', ''));
            $audience = $request->input('audience', 'inactive');
            $custom   = trim($request->input('custom_phones', ''));

            if (!$name || !$mediaId || !$caption) {
                DB::rollBack();
                return response()->json(['success' => false, 'error' => 'Missing required fields']);
            }

            // SECURITY FIX: Prevent concurrent campaigns with pessimistic locking
            $runningCount = DB::table('wa_campaigns')
                ->where('status', 'running')
                ->lockForUpdate()
                ->count();

            if ($runningCount > 0) {
                DB::rollBack();
                return response()->json(['success' => false, 'error' => 'Another campaign is currently running. Please wait for it to complete.']);
            }

            // Build phone list
            if ($audience === 'custom') {
                $lines  = preg_split('/[\r\n,]+/', $custom);
                $phones = array_values(array_filter(array_map('trim', $lines)));
            } elseif ($audience === 'inactive') {
                $rows   = DB::select("SELECT DISTINCT u.phone FROM users u
                    WHERE u.phone IS NOT NULL AND u.phone != '' AND u.status = 1
                    AND u.id IN (SELECT user_id FROM orders WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY))
                    AND u.id NOT IN (SELECT user_id FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY))");
                $phones = array_column($rows, 'phone');
            } else {
                $rows   = DB::select("SELECT DISTINCT u.phone FROM users u
                    WHERE u.phone IS NOT NULL AND u.phone != '' AND u.status = 1
                    AND u.id IN (SELECT DISTINCT user_id FROM orders WHERE user_id IS NOT NULL)");
                $phones = array_column($rows, 'phone');
            }

            if (empty($phones)) {
                DB::rollBack();
                return response()->json(['success' => false, 'error' => 'No phone numbers found for this audience']);
            }

            // Insert campaign record
            DB::insert("INSERT INTO wa_campaigns (name, image_id, caption, audience, total, sent, failed, started_at, status)
                VALUES (?, ?, ?, ?, ?, 0, 0, NOW(), 'running')",
                [$name, $mediaId, $caption, $audience, count($phones)]);
            $campaignId = DB::getPdo()->lastInsertId();

            DB::commit(); // Commit transaction after campaign created

            // Save phone list
            $phoneFile = storage_path('app/wa_phones_' . $campaignId . '.json');
            file_put_contents($phoneFile, json_encode($phones));

            // Write initial status
            $statusFile = storage_path('app/wa_status.json');
            file_put_contents($statusFile, json_encode([
                'status'        => 'running',
                'campaign_id'   => $campaignId,
                'campaign_name' => $name,
                'total'         => count($phones),
                'sent'          => 0,
                'failed'        => 0,
                'failed_numbers'=> [],
                'started_at'    => date('Y-m-d H:i:s'),
            ]));

            // SECURITY FIX: Replace exec() with queue job (Phase 4 completed)
            SendWhatsAppCampaignJob::dispatch($campaignId)
                ->onQueue(config('whatsapp.queue.campaign_queue', 'whatsapp'));

            return response()->json([
                'success'     => true,
                'campaign_id' => $campaignId,
                'total'       => count($phones),
                'message'     => 'Campaign queued for processing',
            ]);
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::error('WhatsApp campaign failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function status()
    {
        $statusFile = storage_path('app/wa_status.json');
        if (!file_exists($statusFile)) {
            return response()->json(['status' => 'idle']);
        }
        $data = json_decode(file_get_contents($statusFile), true);
        return response()->json($data ?: ['status' => 'idle']);
    }
}
