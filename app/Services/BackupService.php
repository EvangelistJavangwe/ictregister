<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;

class BackupService
{
    /** Run mysqldump and return [localFilePath, filename]. Throws on failure. */
    public function createDump(): array
    {
        $db = config('database.connections.' . config('database.default'));

        $filename = 'ictregister-backup-' . now()->format('Y-m-d_His') . '.sql';
        $dir      = storage_path('app/backups');
        $path     = $dir . DIRECTORY_SEPARATOR . $filename;

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $command = [
            $this->resolveMysqldumpPath(),
            '--host=' . $db['host'],
            '--port=' . $db['port'],
            '--user=' . $db['username'],
        ];
        if (!empty($db['password'])) {
            $command[] = '--password=' . $db['password'];
        }
        $command[] = '--single-transaction';
        $command[] = '--routines';
        $command[] = '--result-file=' . $path;
        $command[] = $db['database'];

        $result = Process::timeout(300)->run($command);

        if (!$result->successful() || !file_exists($path) || filesize($path) === 0) {
            throw new \RuntimeException('Database backup failed. ' . trim($result->errorOutput()));
        }

        return [$path, $filename];
    }

    private function resolveMysqldumpPath(): string
    {
        $candidates = [
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'mysqldump',
        ];

        foreach ($candidates as $candidate) {
            if ($candidate === 'mysqldump' || file_exists($candidate)) {
                return $candidate;
            }
        }

        return 'mysqldump';
    }
}
