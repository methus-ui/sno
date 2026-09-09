<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class DeliverymanAttendanceExport implements FromView, ShouldAutoSize, WithStyles, WithColumnWidths, WithHeadings, WithEvents
{
    use Exportable;
    protected $data;

    public function __construct($data) {
        $this->data = $data;
    }

    public function view(): View
    {
        return view('file-exports.deliveryman-attendance', [
            'data' => $this->data,
        ]);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,  // SL
            'B' => 20,  // Delivery Man Name
            'C' => 15,  // Date
            'D' => 15,  // Punch In
            'E' => 15,  // Punch Out
            'F' => 18,  // Working Hours
            'G' => 15,  // Status
            'H' => 20,  // Phone
        ];
    }

    public function styles(Worksheet $sheet) {
        $sheet->getStyle('A1:H1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A2:B3')->getFont()->setBold(true);
        $sheet->getStyle('C2:C3')->getFont()->setBold(true);
        $sheet->getStyle('A4:H4')->getFont()->setBold(true)->getColor()
            ->setARGB('FFFFFF');

        // Header background color
        $sheet->getStyle('A4:H4')->getFill()->applyFromArray([
            'fillType' => Fill::FILL_SOLID,
            'color' => ['rgb' => '005D5F'],
        ]);

        $sheet->setShowGridlines(false);
        
        // Borders for all cells
        $sheet->getStyle('A1:H'.$this->data['attendances']->count() + 4)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ]);

        return [
            'A1:H1' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            'A2:B3' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_TOP,
                ],
            ],
            'C2:H3' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_TOP,
                    'wrapText' => true,
                ],
            ],
            'A4:H'.$this->data['attendances']->count() + 4 => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $rowCount = $this->data['attendances']->count() + 4;
                
                // Merge cells for header and info sections
                $event->sheet->mergeCells('A1:H1');
                
                // Delivery man info row
                $event->sheet->mergeCells('A2:B2');
                $event->sheet->mergeCells('C2:H2');
                
                // Filter criteria row
                $event->sheet->mergeCells('A3:B3');
                $event->sheet->mergeCells('C3:H3');

                // Set row heights
                $event->sheet->getDefaultRowDimension()->setRowHeight(25);
                $event->sheet->getRowDimension(1)->setRowHeight(30);
                $event->sheet->getRowDimension(2)->setRowHeight(60); // Adjusted for delivery man info
                $event->sheet->getRowDimension(3)->setRowHeight(50); // Adjusted for filter criteria
                $event->sheet->getRowDimension(4)->setRowHeight(30); // Header row

                // Adjust column widths for better text wrapping
                $event->sheet->getColumnDimension('C')->setWidth(25);
                $event->sheet->getColumnDimension('D')->setWidth(20);
                $event->sheet->getColumnDimension('E')->setWidth(20);
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