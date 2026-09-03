<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use App\Services\BackupService;
use App\Services\SharePointBackupService;
use Illuminate\Http\Request;
use RuntimeException;

class BackupController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditTrail::where('action', 'backup');

        if ($request->filled('status')) {
            $query->where('badge_status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('module', $request->type);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $history = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        $recentRuns   = AuditTrail::where('action', 'backup')->where('created_at', '>=', now()->subDays(30));
        $totalRuns    = (clone $recentRuns)->count();
        $successRuns  = (clone $recentRuns)->where('badge_status', 'success')->count();

        $lastRun      = AuditTrail::where('action', 'backup')->latest('created_at')->first();
        $lastSuccess  = AuditTrail::where('action', 'backup')->where('badge_status', 'success')->latest('created_at')->first();
        $lastFailure  = AuditTrail::where('action', 'backup')->whereIn('badge_status', ['failed', 'warning'])->latest('created_at')->first();

        return view('backup.index', compact(
            'history', 'totalRuns', 'successRuns', 'lastRun', 'lastSuccess', 'lastFailure'
        ));
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
        } catch (\Throwable $e) {
            AuditTrail::log('backup', 'System', 'SharePoint backup upload failed: ' . $e->getMessage(), 'failed', null);
            return back()->with('error', 'SharePoint upload failed: ' . $e->getMessage());
        } finally {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }
}
