<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use App\Services\SharePointBackupService;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class BackupController extends Controller
{
    public function index()
    {
        return view('backup.index');
    }

    public function download()
    {
        [$path, $filename] = $this->runMysqldump();

        AuditTrail::log('backup', 'System', "Database backup generated and downloaded: {$filename}", 'success', null);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    public function uploadToSharePoint(SharePointBackupService $sharePoint)
    {
        [$path, $filename] = $this->runMysqldump();

        try {
            $sharePoint->upload($path, $filename);
            AuditTrail::log('backup', 'System', "Database backup uploaded to SharePoint: {$filename}", 'success', null);
            return back()->with('success', "Backup \"{$filename}\" uploaded to SharePoint successfully.");
        } catch (RuntimeException $e) {
            AuditTrail::log('backup', 'System', 'SharePoint backup upload failed: ' . $e->getMessage(), 'failed', null);
            return back()->with('error', 'SharePoint upload failed: ' . $e->getMessage());
        } finally {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    /** Run mysqldump and return [localFilePath, filename]. Aborts the request on failure. */
    private function runMysqldump(): array
    {
        $db = config('database.connections.' . config('database.default'));

        $filename = 'ictregister-backup-' . now()->format('Y-m-d_His') . '.sql';
        $dir      = storage_path('app/backups');
        $path     = $dir . DIRECTORY_SEPARATOR . $filename;

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $mysqldump = $this->resolveMysqldumpPath();

        $command = [
            $mysqldump,
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
            AuditTrail::log('backup', 'System', 'Database backup failed: ' . $result->errorOutput(), 'failed', null);
            abort(500, 'Database backup failed. ' . trim($result->errorOutput()));
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
