<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\Store15ItemsExport;

class ExportStore15Items extends Command
{
    protected $signature = 'export:store15items';
    protected $description = 'Export all items from store_id = 15 to Excel';

    public function handle()
    {
        $fileName = 'store_15_items.xlsx';
        $path = storage_path('app/public/' . $fileName);

        Excel::store(new Store15ItemsExport, 'public/' . $fileName);

        $this->info("✅ Export completed! File saved at: {$path}");
    }
}
