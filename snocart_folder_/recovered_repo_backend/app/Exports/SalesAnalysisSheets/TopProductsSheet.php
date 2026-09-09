<?php

namespace App\Exports\SalesAnalysisSheets;

use App\Models\OrderDetail;
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

class TopProductsSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize
{
    protected $startDate;
    protected $endDate;
    protected $rank = 0;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * Fetch top 100 products by revenue
     */
    public function collection()
    {
        return OrderDetail::select(
                'order_details.item_id',
                'items.name as product_name',
                'categories.name as category_name',
                DB::raw('SUM(order_details.quantity) as total_quantity'),
                DB::raw('SUM(order_details.price * order_details.quantity) as total_revenue'),
                DB::raw('COUNT(DISTINCT order_details.order_id) as orders_count')
            )
            ->join('items', 'order_details.item_id', '=', 'items.id')
            ->leftJoin('categories', 'items.category_id', '=', 'categories.id')
            ->whereHas('order', function($q) {
                $q->where('order_status', 'delivered')
                  ->whereBetween('created_at', [$this->startDate, $this->endDate]);
            })
            ->groupBy('order_details.item_id', 'items.name', 'categories.name')
            ->orderByDesc('total_revenue')
            ->limit(100)
            ->get();
    }

    /**
     * Map each row to Excel format
     */
    public function map($row): array
    {
        $this->rank++;

        return [
            $this->rank,
            $row->product_name,
            $row->category_name ?? 'Uncategorized',
            number_format($row->total_quantity),
            '₹' . number_format($row->total_revenue, 2),
            number_format($row->orders_count),
        ];
    }

    /**
     * Define column headings
     */
    public function headings(): array
    {
        return [
            'Rank',
            'Product Name',
            'Category',
            'Qty Sold',
            'Revenue',
            'Orders Count',
        ];
    }

    /**
     * Sheet title
     */
    public function title(): string
    {
        return 'Top Products';
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

        // Highlight top 10 products with yellow background
        for ($i = 2; $i <= 11; $i++) {
            $styles[$i] = [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFEB9C']
                ],
            ];
        }

        return $styles;
    }
}
