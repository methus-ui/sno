<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SalesAnalysisExport implements WithMultipleSheets
{
    protected $startDate;
    protected $endDate;

    public function __construct()
    {
        // Last 8 months: from start of 8 months ago to end of current month
        $this->startDate = now()->subMonths(8)->startOfMonth();
        $this->endDate = now()->endOfMonth();
    }

    /**
     * Return array of sheets for the workbook
     *
     * @return array
     */
    public function sheets(): array
    {
        return [
            new SalesAnalysisSheets\RevenueSheet($this->startDate, $this->endDate),
            new SalesAnalysisSheets\TopProductsSheet($this->startDate, $this->endDate),
            new SalesAnalysisSheets\StorePerformanceSheet($this->startDate, $this->endDate),
            new SalesAnalysisSheets\OrderPatternsSheet($this->startDate, $this->endDate),
        ];
    }
}
