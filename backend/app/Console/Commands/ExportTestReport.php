<?php

namespace App\Console\Commands;

use App\Exports\TestReportExport;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ExportTestReport extends Command
{
    protected $signature = 'test:export-excel';

    protected $description = 'Run PHPUnit tests and export results to an Excel file';

    public function handle(): int
    {
        $this->info('Running tests...');

        $xmlPath = storage_path('app/test-results.xml');

        // Run PHPUnit tests with JUnit XML output
        $exitCode = 0;
        passthru('cd ' . base_path() . ' && php artisan test --log-junit=' . $xmlPath . ' 2>&1', $exitCode);

        if (!file_exists($xmlPath)) {
            $this->error('Test results XML file was not generated.');
            return 1;
        }

        $this->info('Parsing test results...');

        $xml = simplexml_load_file($xmlPath);
        if ($xml === false) {
            $this->error('Failed to parse test results XML.');
            return 1;
        }

        $testResults = [];
        $serialNo = 0;
        $totalTests = 0;
        $totalPassed = 0;
        $totalFailed = 0;
        $totalAssertions = 0;
        $totalTime = 0.0;
        $date = now()->format('Y-m-d');

        foreach ($xml->testsuite as $topSuite) {
            $this->parseTestSuite($topSuite, $testResults, $serialNo, $totalTests, $totalPassed, $totalFailed, $totalAssertions, $totalTime, $date);
        }

        // If the root element itself has testcases or is a single testsuite
        if ($serialNo === 0) {
            $this->parseTestSuite($xml, $testResults, $serialNo, $totalTests, $totalPassed, $totalFailed, $totalAssertions, $totalTime, $date);
        }

        $summary = [
            'total' => $totalTests,
            'passed' => $totalPassed,
            'failed' => $totalFailed,
            'assertions' => $totalAssertions,
            'time' => round($totalTime, 4),
            'date' => $date,
        ];

        $this->info('Generating Excel report...');

        $fullPath = storage_path('app/ClinicOS_Test_Report.xlsx');

        // Use direct PhpSpreadsheet write to ensure exact path
        $export = new TestReportExport($testResults, $summary);
        $writer = Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
        file_put_contents($fullPath, $writer);
        $this->info("Test report exported to: {$fullPath}");
        $this->info("Total: {$totalTests} tests, {$totalPassed} passed, {$totalFailed} failed, {$totalAssertions} assertions");

        // Clean up XML file
        @unlink($xmlPath);

        return 0;
    }

    private function parseTestSuite($suite, array &$results, int &$serialNo, int &$totalTests, int &$totalPassed, int &$totalFailed, int &$totalAssertions, float &$totalTime, string $date): void
    {
        // Process test cases directly in this suite
        foreach ($suite->testcase as $testcase) {
            $serialNo++;
            $totalTests++;

            $className = (string) $testcase['class'];
            $testName = (string) $testcase['name'];
            $assertions = (int) $testcase['assertions'];
            $time = (float) $testcase['time'];

            $totalAssertions += $assertions;
            $totalTime += $time;

            // Determine status
            $failed = isset($testcase->failure) || isset($testcase->error);
            if ($failed) {
                $status = 'Fail';
                $totalFailed++;
            } else {
                $status = 'Pass';
                $totalPassed++;
            }

            // Extract module from class name (e.g., Tests\Feature\AuthTest -> Feature)
            $parts = explode('\\', $className);
            $module = count($parts) >= 2 ? $parts[count($parts) - 2] : 'Unknown';

            // Short class name
            $shortClass = class_basename($className);

            // Convert camelCase test name to readable format
            $readableName = $this->formatTestName($testName);

            $results[] = [
                $serialNo,
                $module,
                $shortClass,
                $readableName,
                $status,
                $assertions,
                round($time, 4),
                $date,
            ];
        }

        // Recurse into nested testsuites
        foreach ($suite->testsuite as $childSuite) {
            $this->parseTestSuite($childSuite, $results, $serialNo, $totalTests, $totalPassed, $totalFailed, $totalAssertions, $totalTime, $date);
        }
    }

    private function formatTestName(string $name): string
    {
        // Remove 'test_' or 'test' prefix
        $name = preg_replace('/^test_?/', '', $name);

        // Convert snake_case to words
        $name = str_replace('_', ' ', $name);

        // Convert camelCase to words
        $name = preg_replace('/([a-z])([A-Z])/', '$1 $2', $name);

        return ucfirst(strtolower($name));
    }
}
