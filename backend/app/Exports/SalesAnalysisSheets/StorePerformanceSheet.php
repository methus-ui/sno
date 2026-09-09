<?php

namespace App\Exports\SalesAnalysisSheets;

use App\Models\OrderTransaction;
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

class StorePerformanceSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * Fetch store performance data
     */
    public function collection()
    {
        return DB::table('order_transactions')
            ->select(
                'stores.name as store_name',
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(order_transactions.order_amount) as total_revenue'),
                DB::raw('AVG(order_transactions.order_amount) as avg_order_value'),
                DB::raw('SUM(order_transactions.admin_commission) as commission_earned'),
                DB::raw('SUM(order_transactions.store_amount) as store_earnings'),
                'orders.store_id'
            )
            ->join('orders', 'order_transactions.order_id', '=', 'orders.id')
            ->join('stores', 'orders.store_id', '=', 'stores.id')
            ->where('orders.order_status', 'delivered')
            ->whereBetween('order_transactions.created_at', [$this->startDate, $this->endDate])
            ->groupBy('orders.store_id', 'stores.name')
            ->orderByDesc('total_revenue')
            ->get();
    }

    /**
     * Map each row to Excel format
     */
    public function map($row): array
    {
        // Calculate commission rate with division by zero protection
        $commissionRate = $row->total_revenue > 0
            ? ($row->commission_earned / $row->total_revenue) * 100
            : 0;

        return [
            $row->store_name,
            number_format($row->total_orders),
            '₹' . number_format($row->total_revenue, 2),
            '₹' . number_format($row->avg_order_value, 2),
            '₹' . number_format($row->commission_earned, 2),
            '₹' . number_format($row->store_earnings, 2),
            number_format($commissionRate, 2) . '%',
        ];
    }

    /**
     * Define column headings
     */
    public function headings(): array
    {
        return [
            'Store Name',
            'Orders',
            'Revenue',
            'Avg Order',
            'Commission',
            'Store Earnings',
            'Commission %',
        ];
    }

    /**
     * Sheet title
     */
    public function title(): string
    {
        return 'Store Performance';
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
