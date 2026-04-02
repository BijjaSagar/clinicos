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

class TestResultsSheet implements FromArray, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected array $testResults;

    public function __construct(array $testResults)
    {
        $this->testResults = $testResults;
    }

    public function array(): array
    {
        return $this->testResults;
    }

    public function headings(): array
    {
        return ['S.No', 'Module', 'Test Class', 'Test Name', 'Status', 'Assertions', 'Time (s)', 'Date'];
    }

    public function title(): string
    {
        return 'Test Results';
    }

    public function styles(Worksheet $sheet): array
    {
        // Bold headers with background color
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Style status cells with green/red
        $rowCount = count($this->testResults);
        for ($i = 2; $i <= $rowCount + 1; $i++) {
            $statusCell = 'E' . $i;
            $statusValue = $sheet->getCell($statusCell)->getValue();

            if ($statusValue === 'Pass') {
                $sheet->getStyle($statusCell)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '006100']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'C6EFCE'],
                    ],
                ]);
            } elseif ($statusValue === 'Fail') {
                $sheet->getStyle($statusCell)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '9C0006']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFC7CE'],
                    ],
                ]);
            }
        }

        // Center-align specific columns
        $dataRange = 'A2:H' . ($rowCount + 1);
        $sheet->getStyle($dataRange)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A2:A' . ($rowCount + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E2:G' . ($rowCount + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H2:H' . ($rowCount + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [];
    }
}
