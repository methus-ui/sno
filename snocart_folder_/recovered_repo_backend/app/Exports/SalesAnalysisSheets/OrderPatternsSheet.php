<?php

namespace App\Exports\SalesAnalysisSheets;

use App\Models\Order;
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

class OrderPatternsSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize
{
    protected $startDate;
    protected $endDate;
    protected $peakHours = [];
    protected $weekendRows = [];
    protected $currentRow = 2; // Start from row 2 (row 1 is headers)

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->calculatePeakHours();
    }

    /**
     * Calculate peak hour for each date
     */
    protected function calculatePeakHours()
    {
        $peakData = DB::table('orders')
            ->select(
                DB::raw('DATE(created_at) as order_date'),
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as order_count')
            )
            ->where('order_status', 'delivered')
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->groupBy(DB::raw('DATE(created_at)'), DB::raw('HOUR(created_at)'))
            ->get();

        // Find peak hour for each date
        $groupedByDate = $peakData->groupBy('order_date');

        foreach ($groupedByDate as $date => $hours) {
            $peakHour = $hours->sortByDesc('order_count')->first();
            if ($peakHour) {
                $this->peakHours[$date] = sprintf('%02d:00', $peakHour->hour);
            }
        }
    }

    /**
     * Fetch daily order patterns
     */
    public function collection()
    {
        return DB::table('orders')
            ->select(
                DB::raw('DATE(created_at) as order_date'),
                DB::raw('DAYNAME(created_at) as day_of_week'),
                DB::raw('COUNT(*) as orders_count'),
                DB::raw('SUM(order_amount) as revenue'),
                DB::raw('AVG(order_amount) as avg_order_value'),
                DB::raw('SUM(CASE WHEN order_type = "delivery" THEN 1 ELSE 0 END) as delivery_count'),
                DB::raw('SUM(CASE WHEN order_type = "take_away" THEN 1 ELSE 0 END) as takeaway_count'),
                DB::raw('SUM(CASE WHEN payment_method = "cash_on_delivery" THEN 1 ELSE 0 END) as cod_count'),
                DB::raw('SUM(CASE WHEN payment_method != "cash_on_delivery" THEN 1 ELSE 0 END) as digital_count')
            )
            ->where('order_status', 'delivered')
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->groupBy(DB::raw('DATE(created_at)'), DB::raw('DAYNAME(created_at)'))
            ->orderBy('order_date', 'DESC')
            ->get();
    }

    /**
     * Map each row to Excel format
     */
    public function map($row): array
    {
        // Calculate percentages with division by zero protection
        $deliveryPercent = $row->orders_count > 0
            ? ($row->delivery_count / $row->orders_count) * 100
            : 0;

        $takeawayPercent = $row->orders_count > 0
            ? ($row->takeaway_count / $row->orders_count) * 100
            : 0;

        $codPercent = $row->orders_count > 0
            ? ($row->cod_count / $row->orders_count) * 100
            : 0;

        $digitalPercent = $row->orders_count > 0
            ? ($row->digital_count / $row->orders_count) * 100
            : 0;

        // Get peak hour for this date
        $peakHour = $this->peakHours[$row->order_date] ?? 'N/A';

        // Track weekend rows for styling
        if (in_array($row->day_of_week, ['Saturday', 'Sunday'])) {
            $this->weekendRows[] = $this->currentRow;
        }
        $this->currentRow++;

        return [
            date('d M Y', strtotime($row->order_date)),
            number_format($row->orders_count),
            '₹' . number_format($row->revenue, 2),
            '₹' . number_format($row->avg_order_value, 2),
            $peakHour,
            number_format($deliveryPercent, 1) . '%',
            number_format($takeawayPercent, 1) . '%',
            number_format($codPercent, 1) . '%',
            number_format($digitalPercent, 1) . '%',
            $row->day_of_week,
        ];
    }

    /**
     * Define column headings
     */
    public function headings(): array
    {
        return [
            'Date',
            'Orders',
            'Revenue',
            'Avg Order',
            'Peak Hour',
            'Delivery %',
            'Takeaway %',
            'COD %',
            'Digital %',
            'Day',
        ];
    }

    /**
     * Sheet title
     */
    public function title(): string
    {
        return 'Order Patterns';
    }

    /**
     * Apply styling to the sheet
     */
    public function styles(Worksheet $sheet)
    {
        // Header row styling
        $styles = [
            1 => [
                'font' => ['bold' => true, 'size' => 11],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D9D9D9']
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];

        // Highlight weekend rows with blue background
        foreach ($this->weekendRows as $row) {
            $styles[$row] = [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'DDEBF7']
                ],
            ];
        }

        return $styles;
    }
}
