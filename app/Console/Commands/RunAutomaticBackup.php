<?php

namespace App\Console\Commands;

use App\Models\AuditTrail;
use App\Services\BackupService;
use App\Services\SharePointBackupService;
use Illuminate\Console\Command;
use RuntimeException;

class RunAutomaticBackup extends Command
{
    protected $signature = 'ict:auto-backup {--only-if-missed : Skip if today\'s scheduled backup already ran (used for the VM-startup catch-up trigger)}';
    protected $description = 'Create a database backup, upload it to SharePoint, and keep a local copy as a fallback';

    public function handle(BackupService $backup, SharePointBackupService $sharePoint): void
    {
        if ($this->option('only-if-missed') && $this->alreadyRanToday()) {
            $this->info('Today\'s automatic backup already ran — skipping catch-up.');
            return;
        }

        try {
            [$path, $filename] = $backup->createDump();
        } catch (RuntimeException $e) {
            AuditTrail::create([
                'username'     => 'System',
                'action'       => 'backup',
                'module'       => 'Scheduler',
                'description'  => 'Automatic database backup failed: ' . $e->getMessage(),
                'ip_address'   => '127.0.0.1',
                'badge_status' => 'failed',
            ]);
            $this->error('Backup failed: ' . $e->getMessage());
            return;
        }

        // Local copy is kept regardless of SharePoint outcome — it's the fallback.
        try {
            $sharePoint->upload($path, $filename);
            AuditTrail::create([
                'username'     => 'System',
                'action'       => 'backup',
                'module'       => 'Scheduler',
                'description'  => "Automatic backup created and uploaded to SharePoint: {$filename}",
                'ip_address'   => '127.0.0.1',
                'badge_status' => 'success',
            ]);
            $this->info("Backup created and uploaded to SharePoint: {$filename}");
        } catch (\Throwable $e) {
            AuditTrail::create([
                'username'     => 'System',
                'action'       => 'backup',
                'module'       => 'Scheduler',
                'description'  => "Automatic backup created locally ({$filename}) but SharePoint upload failed: " . $e->getMessage(),
                'ip_address'   => '127.0.0.1',
                'badge_status' => 'warning',
            ]);
            $this->warn("Backup kept locally ({$filename}); SharePoint upload failed: " . $e->getMessage());
        }
    }

    /** True if today's midnight schedule already fired (success, warning, or failed — it still ran). */
    private function alreadyRanToday(): bool
    {
        return AuditTrail::where('action', 'backup')
            ->where('module', 'Scheduler')
            ->whereDate('created_at', now()->toDateString())
            ->exists();
    }
}
