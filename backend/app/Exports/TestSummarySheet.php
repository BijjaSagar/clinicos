<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class TestSummarySheet implements FromArray, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected array $summary;

    public function __construct(array $summary)
    {
        $this->summary = $summary;
    }

    public function array(): array
    {
        return [
            ['Total Tests', $this->summary['total']],
            ['Passed', $this->summary['passed']],
            ['Failed', $this->summary['failed']],
            ['Total Assertions', $this->summary['assertions']],
            ['Total Time', $this->summary['time'] . 's'],
            ['Date', $this->summary['date']],
        ];
    }

    public function headings(): array
    {
        return ['Metric', 'Value'];
    }

    public function title(): string
    {
        return 'Summary';
    }

    public function styles(Worksheet $sheet): array
    {
        // Bold headers
        $sheet->getStyle('A1:B1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Bold metric labels
        $sheet->getStyle('A2:A7')->applyFromArray([
            'font' => ['bold' => true],
        ]);

        // Green for passed row
        $sheet->getStyle('B3')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '006100']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'C6EFCE'],
            ],
        ]);

        // Red for failed row (if any failures)
        if ($this->summary['failed'] > 0) {
            $sheet->getStyle('B4')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '9C0006']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFC7CE'],
                ],
            ]);
        }

        $sheet->getStyle('B2:B7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [];
    }
}
