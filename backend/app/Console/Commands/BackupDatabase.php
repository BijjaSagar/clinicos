<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database {--keep=30 : Days to keep backups}';
    protected $description = 'Create a MySQL database backup and clean old backups';

    public function handle(): int
    {
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port', 3306);

        $filename = "backup_{$database}_" . now()->format('Y-m-d_His') . '.sql.gz';
        $backupPath = storage_path("app/backups");

        // Ensure backup directory exists
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        $filepath = "{$backupPath}/{$filename}";

        $this->info("Starting backup of database: {$database}");

        // Build mysqldump command
        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers --quick %s | gzip > %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($filepath)
        );

        $result = null;
        $output = null;
        exec($command . ' 2>&1', $output, $result);

        if ($result !== 0) {
            $error = implode("\n", $output);
            $this->error("Backup failed: {$error}");
            Log::error('Database backup failed', ['error' => $error]);
            return self::FAILURE;
        }

        $size = filesize($filepath);
        $sizeFormatted = number_format($size / 1024 / 1024, 2) . ' MB';
        $this->info("Backup created: {$filename} ({$sizeFormatted})");
        Log::info('Database backup created', ['file' => $filename, 'size' => $sizeFormatted]);

        // Clean old backups
        $keepDays = (int) $this->option('keep');
        $this->cleanOldBackups($backupPath, $keepDays);

        return self::SUCCESS;
    }

    private function cleanOldBackups(string $path, int $keepDays): void
    {
        $files = glob("{$path}/backup_*.sql.gz");
        $threshold = now()->subDays($keepDays)->timestamp;
        $deleted = 0;

        foreach ($files as $file) {
            if (filemtime($file) < $threshold) {
                unlink($file);
                $deleted++;
            }
        }

        if ($deleted > 0) {
            $this->info("Cleaned {$deleted} old backup(s) (older than {$keepDays} days)");
            Log::info('Old backups cleaned', ['deleted' => $deleted, 'keep_days' => $keepDays]);
        }
    }
}
