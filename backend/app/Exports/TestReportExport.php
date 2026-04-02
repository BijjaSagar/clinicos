<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TestReportExport implements WithMultipleSheets
{
    protected array $testResults;
    protected array $summary;

    public function __construct(array $testResults, array $summary)
    {
        $this->testResults = $testResults;
        $this->summary = $summary;
    }

    public function sheets(): array
    {
        return [
            'Test Results' => new TestResultsSheet($this->testResults),
            'Summary' => new TestSummarySheet($this->summary),
        ];
    }
}
