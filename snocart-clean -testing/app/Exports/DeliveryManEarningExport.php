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

class DeliveryManEarningExport implements FromView, ShouldAutoSize, WithStyles, WithColumnWidths, WithHeadings, WithEvents
{
    use Exportable;
    protected $data;

    public function __construct($data) {
        $this->data = $data;
    }

    public function view(): View
    {
        return view('file-exports.deliveryman-earning', [
            'data' => $this->data,
        ]);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,  // SL
            'B' => 15,  // Order ID
            'C' => 20,  // Payment Method
            'D' => 15,  // Order Amount
            'E' => 15,  // Distance
            'F' => 20,  // Actual Delivery Charge
            'G' => 20,  // Convenience Fee
            'H' => 20,  // Delivery Fee Earned
            'I' => 15,  // Tips
            'J' => 20,  // Total Earning
        ];
    }

    public function styles(Worksheet $sheet) {
        $sheet->getStyle('A1:J1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A2:J3')->getFont()->setBold(true);
        $sheet->getStyle('A4:J4')->getFont()->setBold(true)->getColor()
            ->setARGB('FFFFFF');

        // Header background color
        $sheet->getStyle('A4:J4')->getFill()->applyFromArray([
            'fillType' => Fill::FILL_SOLID,
            'color' => ['rgb' => '005D5F'],
        ]);

        $sheet->setShowGridlines(false);
        
        // Borders for all cells
        $sheet->getStyle('A1:J'.$this->data['earnings']->count() + 4)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ]);

        return [
            'A1:J1' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            'A2:J3' => [
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            'A4:J'.$this->data['earnings']->count() + 4 => [
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
                // Merge cells for header and info sections
                $event->sheet->mergeCells('A1:J1');
                $event->sheet->mergeCells('A2:B2');
                $event->sheet->mergeCells('C2:J2');
                $event->sheet->mergeCells('A3:B3');
                $event->sheet->mergeCells('C3:J3');

                // Set row heights
                $event->sheet->getDefaultRowDimension()->setRowHeight(25);
                $event->sheet->getRowDimension(1)->setRowHeight(30);
                $event->sheet->getRowDimension(2)->setRowHeight(80);
                $event->sheet->getRowDimension(3)->setRowHeight(30);

                // Text alignment
                $event->sheet->getStyle('C2:J2')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $event->sheet->getStyle('C3:J3')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT)
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