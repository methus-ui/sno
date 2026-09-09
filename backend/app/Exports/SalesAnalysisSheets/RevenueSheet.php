<?php

namespace App\Exports\SalesAnalysisSheets;

use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class RevenueSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize
{
    protected $startDate;
    protected $endDate;
    protected $previousRevenue = null;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * Fetch monthly revenue data
     */
    public function collection()
    {
        return Order::select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('DATE_FORMAT(created_at, "%b %Y") as month_label'),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(order_amount) as total_revenue'),
                DB::raw('AVG(order_amount) as avg_order_value')
            )
            ->where('order_status', 'delivered')
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->groupBy(DB::raw('DATE_FORMAT(created_at, "%Y-%m")'), DB::raw('DATE_FORMAT(created_at, "%b %Y")'))
            ->orderBy('month', 'ASC')
            ->get();
    }

    /**
     * Map each row to Excel format
     */
    public function map($row): array
    {
        // Calculate growth percentage
        $growthPercent = '-';
        $trend = '-';

        if ($this->previousRevenue !== null && $this->previousRevenue > 0) {
            $growth = (($row->total_revenue - $this->previousRevenue) / $this->previousRevenue) * 100;
            $growthPercent = number_format($growth, 2) . '%';

            if ($growth > 0) {
                $trend = '↑';
            } elseif ($growth < 0) {
                $trend = '↓';
            } else {
                $trend = '-';
            }
        }

        $this->previousRevenue = $row->total_revenue;

        return [
            $row->month_label,
            '₹' . number_format($row->total_revenue, 2),
            number_format($row->total_orders),
            '₹' . number_format($row->avg_order_value, 2),
            $growthPercent,
            $trend,
        ];
    }

    /**
     * Define column headings
     */
    public function headings(): array
    {
        return [
            'Month',
            'Total Revenue',
            'Total Orders',
            'Avg Order Value',
            'Growth %',
            'Trend',
        ];
    }

    /**
     * Sheet title
     */
    public function title(): string
    {
        return 'Revenue Trends';
    }

    /**
     * Apply styling to the sheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Header row styling
            1 => [
                'font' => ['bold' => true, 'size' => 11],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D9D9D9']
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}
