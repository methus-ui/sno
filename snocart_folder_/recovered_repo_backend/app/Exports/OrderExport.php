<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class OrderExport implements FromView, ShouldAutoSize, WithStyles, WithColumnWidths, WithHeadings, WithEvents
{
    use Exportable;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        return view('file-exports.order-export', [
            'data' => $this->data,
        ]);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,
            'B' => 20,
            'C' => 25,
            'D' => 25,
            'E' => 20,
            'F' => 20,
            'G' => 20,
            'H' => 20,
            'I' => 20,
            'J' => 20,
            'K' => 20,
            'L' => 20,
            'M' => 20,
            'N' => 25,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $rowCount = $this->data['orders']->count() + 3; // header rows + data

        // Header styling (row 3)
        $sheet->getStyle('A3:N3')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A3:N3')->getFill()->setFillType('solid')->getStartColor()->setRGB('005D5F');
        $sheet->getStyle('A3:N3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Border around data
        $sheet->getStyle("A3:N{$rowCount}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ]);

        // Highlight columns (optional — for example K:N)
        $sheet->getStyle("K4:N{$rowCount}")->getFill()->setFillType('solid')->getStartColor()->setRGB('FFE599');

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $orderCount = $this->data['orders']->count();
                $lastRow = $orderCount + 3;

                // Merge and style title rows
                $sheet->mergeCells('A1:N1');
                $sheet->mergeCells('A2:C2');
                $sheet->mergeCells('D2:N2');

                // Center align titles
                $event->sheet->getStyle('A1:N1')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // Adjust heights
                $sheet->getRowDimension(1)->setRowHeight(40);
                $sheet->getRowDimension(2)->setRowHeight(80);
                $sheet->getDefaultRowDimension()->setRowHeight(25);

                // Align all data cells nicely
                $event->sheet->getStyle("A3:N{$lastRow}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
            },
        ];
    }

    public function headings(): array
    {
        return [
           '1'
        ];
    }
}
