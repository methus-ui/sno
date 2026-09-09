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

class DayCloseExport implements FromView, ShouldAutoSize, WithStyles, WithColumnWidths, WithHeadings, WithEvents
{
    use Exportable;
    protected $data;

    public function __construct($data) {
        $this->data = $data;
    }

    public function view(): View
    {
        return view('file-exports.deliveryman-day-close', [
            'data' => $this->data,
        ]);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,   // SL
            'B' => 15,  // Order ID
            'C' => 25,  // Customer
            'D' => 18,  // Order Amount
            'E' => 20,  // Outside Purchase
            'F' => 22,  // Payment Method
            'G' => 22,  // Delivered At
        ];
    }

    public function styles(Worksheet $sheet) {
        $lastCol = 'G';
        $dataRows = $this->data['dateOrders']->count();

        $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle("A2:{$lastCol}2")->getFont()->setBold(true);
        $sheet->getStyle("A3:{$lastCol}3")->getFont()->setBold(true);
        $sheet->getStyle("A4:{$lastCol}4")->getFont()->setBold(true);
        $sheet->getStyle("A5:{$lastCol}5")->getFont()->setBold(true)->getColor()->setARGB('FFFFFF');

        $sheet->getStyle("A5:{$lastCol}5")->getFill()->applyFromArray([
            'fillType' => Fill::FILL_SOLID,
            'color' => ['rgb' => '005D5F'],
        ]);

        $sheet->setShowGridlines(false);

        // 6 summary rows: 1 separator + 1 header + 4 data rows
        $summaryHeaderRow = $dataRows + 7;
        $totalCollectRow  = $dataRows + 11;
        $totalRows        = $dataRows + 11;

        $sheet->getStyle("A1:{$lastCol}{$totalRows}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ]);

        // Financial Summary header row — same dark teal as column headers
        $sheet->getStyle("A{$summaryHeaderRow}:{$lastCol}{$summaryHeaderRow}")
            ->getFont()->setBold(true)->setSize(12)->getColor()->setARGB('FFFFFF');
        $sheet->getStyle("A{$summaryHeaderRow}:{$lastCol}{$summaryHeaderRow}")
            ->getFill()->applyFromArray([
                'fillType' => Fill::FILL_SOLID,
                'color'    => ['rgb' => '005D5F'],
            ]);

        // "Total amount to collect" row — highlighted amber so it stands out
        $sheet->getStyle("A{$totalCollectRow}:{$lastCol}{$totalCollectRow}")
            ->getFont()->setBold(true);
        $sheet->getStyle("A{$totalCollectRow}:{$lastCol}{$totalCollectRow}")
            ->getFill()->applyFromArray([
                'fillType' => Fill::FILL_SOLID,
                'color'    => ['rgb' => 'FFF3CD'],
            ]);

        return [
            "A1:{$lastCol}1" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ],
            "A5:{$lastCol}{$totalRows}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $lastCol = 'G';
                $dataRows = $this->data['dateOrders']->count();

                $event->sheet->mergeCells("A1:{$lastCol}1");
                $event->sheet->mergeCells("A2:B2");
                $event->sheet->mergeCells("C2:{$lastCol}2");
                $event->sheet->mergeCells("A3:B3");
                $event->sheet->mergeCells("C3:{$lastCol}3");
                $event->sheet->mergeCells("A4:B4");
                $event->sheet->mergeCells("C4:{$lastCol}4");

                $event->sheet->getDefaultRowDimension()->setRowHeight(25);
                $event->sheet->getRowDimension(1)->setRowHeight(30);
                $event->sheet->getRowDimension(2)->setRowHeight(80);
                $event->sheet->getRowDimension(3)->setRowHeight(30);
                $event->sheet->getRowDimension(4)->setRowHeight(30);

                $event->sheet->getStyle("C2:{$lastCol}2")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $event->sheet->getStyle("C3:{$lastCol}3")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $event->sheet->getStyle("C4:{$lastCol}4")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // Financial summary section
                $summaryHeaderRow = $dataRows + 7;
                $event->sheet->mergeCells("A{$summaryHeaderRow}:{$lastCol}{$summaryHeaderRow}");
                $event->sheet->getRowDimension($summaryHeaderRow)->setRowHeight(30);
                $event->sheet->getStyle("A{$summaryHeaderRow}:{$lastCol}{$summaryHeaderRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
            },
        ];
    }

    public function headings(): array
    {
        return ['1'];
    }
}
