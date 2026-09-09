<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendWhatsAppCampaignJob;

class WhatsAppProcessScheduled extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:process-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process scheduled WhatsApp campaigns that are due';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $this->info('Checking for scheduled WhatsApp campaigns...');

            // Find campaigns that are scheduled and due to be sent
            $campaigns = DB::table('wa_campaigns')
                ->where('status', 'draft')
                ->whereNotNull('scheduled_at')
                ->where('scheduled_at', '<=', now())
                ->get();

            if ($campaigns->isEmpty()) {
                $this->info('No scheduled campaigns due at this time');
                Log::info('WhatsApp scheduled campaigns check: No campaigns due');
                return 0;
            }

            $this->info("Processing {$campaigns->count()} scheduled campaigns...");
            $this->newLine();

            $processed = 0;
            $failed = 0;

            foreach ($campaigns as $campaign) {
                try {
                    // Update campaign status to queued
                    DB::table('wa_campaigns')
                        ->where('id', $campaign->id)
                        ->update([
                            'status' => 'queued',
                            'started_at' => now(),
                            'updated_at' => now()
                        ]);

                    // Dispatch the campaign job
                    SendWhatsAppCampaignJob::dispatch($campaign->id);

                    $this->info("✓ Dispatched campaign: {$campaign->name} (ID: {$campaign->id})");

                    Log::info('WhatsApp campaign dispatched', [
                        'campaign_id' => $campaign->id,
                        'campaign_name' => $campaign->name,
                        'scheduled_at' => $campaign->scheduled_at,
                        'segment_id' => $campaign->segment_id
                    ]);

                    $processed++;

                } catch (\Exception $e) {
                    $this->error("✗ Failed to dispatch campaign {$campaign->id}: {$e->getMessage()}");

                    // Update campaign status to failed
                    DB::table('wa_campaigns')
                        ->where('id', $campaign->id)
                        ->update([
                            'status' => 'failed',
                            'updated_at' => now()
                        ]);

                    Log::error('WhatsApp campaign dispatch failed', [
                        'campaign_id' => $campaign->id,
                        'campaign_name' => $campaign->name,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);

                    $failed++;
                }
            }

            $this->newLine();
            $this->info("Summary:");
            $this->info("  ✓ Processed: {$processed}");
            if ($failed > 0) {
                $this->warn("  ✗ Failed: {$failed}");
            }

            Log::info('WhatsApp scheduled campaigns processing completed', [
                'total_campaigns' => $campaigns->count(),
                'processed' => $processed,
                'failed' => $failed
            ]);

            return $failed > 0 ? 1 : 0;

        } catch (\Exception $e) {
            $this->error('Error processing scheduled campaigns: ' . $e->getMessage());

            Log::error('WhatsApp scheduled campaigns processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return 1;
        }
    }
}
