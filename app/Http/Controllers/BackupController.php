<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use App\Services\BackupService;
use App\Services\SharePointBackupService;
use RuntimeException;

class BackupController extends Controller
{
    public function index()
    {
        return view('backup.index');
    }

    public function download(BackupService $backup)
    {
        try {
            [$path, $filename] = $backup->createDump();
        } catch (RuntimeException $e) {
            AuditTrail::log('backup', 'System', 'Database backup failed: ' . $e->getMessage(), 'failed', null);
            abort(500, $e->getMessage());
        }

        AuditTrail::log('backup', 'System', "Database backup generated and downloaded: {$filename}", 'success', null);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    public function uploadToSharePoint(BackupService $backup, SharePointBackupService $sharePoint)
    {
        try {
            [$path, $filename] = $backup->createDump();
        } catch (RuntimeException $e) {
            AuditTrail::log('backup', 'System', 'Database backup failed: ' . $e->getMessage(), 'failed', null);
            return back()->with('error', 'Database backup failed: ' . $e->getMessage());
        }

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
}
