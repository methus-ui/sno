<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\WhatsAppSegmentsSeeder;
use Illuminate\Support\Facades\Log;

class WhatsAppSeedSegments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:seed-segments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed predefined WhatsApp customer segments';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $this->info('Starting WhatsApp segments seeding...');
            $this->newLine();

            // Run the seeder
            $seeder = new WhatsAppSegmentsSeeder();
            $seeder->setCommand($this);
            $seeder->run();

            $this->newLine();
            $this->info('✓ WhatsApp segments seeding completed successfully!');

            Log::info('WhatsApp segments seeded successfully via artisan command');

            return 0;

        } catch (\Exception $e) {
            $this->error('Error seeding WhatsApp segments: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());

            Log::error('WhatsApp segments seeding failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return 1;
        }
    }
}
